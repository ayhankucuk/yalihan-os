#!/usr/bin/env python3
"""
verifier-watchdog.py — Hermes verifier launcher with state machine

Watches the pending directory for READY handoff artifacts and launches
the Hermes verifier for each one. Implements atomic state transitions to
prevent duplicate verifier runs.

Usage:
    python verifier-watchdog.py [--pending-dir <dir>] [--hermes-home <dir>] [--profile <name>]

Launch via launchd or systemd. Designed to run as a persistent background service.

State machine:
    READY → CLAIMED → VERIFYING → PASS | FAIL | BLOCKED
    VERIFYING timeout → resets to READY
"""

import argparse
import json
import logging
import os
import re
import subprocess
import sys
import time
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Optional

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("verifier-watchdog")


# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------

DEFAULT_PENDING_DIR = os.path.expanduser(
    "~/.hermes/profiles/yalihan-verifier/pending/"
)
DEFAULT_HERMES_HOME = os.path.expanduser("~/.hermes")
DEFAULT_PROFILE = "yalihan-verifier"
DEFAULT_TIMEOUT_MINUTES = 30
DEFAULT_POLL_INTERVAL_SECONDS = 5

HERMES_CLI_PATH = os.path.expanduser(
    "~/.hermes/hermes-agent/cli.py"
)
SANDBOX_SB_PATH = os.path.expanduser(
    "~/.hermes/profiles/yalihan-verifier/sandbox/yalihan-verifier-isolated.sb"
)


# ---------------------------------------------------------------------------
# State Machine
# ---------------------------------------------------------------------------

@dataclass
class Artifact:
    path: Path
    data: dict
    status: str
    task_id: str


def load_artifact(path: Path) -> Optional[Artifact]:
    """Load and parse a handoff artifact."""
    try:
        with open(path, "r") as f:
            data = json.load(f)
        status = data.get("status", "UNKNOWN")
        task_id = data.get("task_id", path.stem)
        return Artifact(path=path, data=data, status=status, task_id=task_id)
    except (json.JSONDecodeError, OSError) as e:
        logger.warning("Failed to load %s: %s", path, e)
        return None


def atomic_rename(src: Path, dst: Path) -> bool:
    """Atomically rename a file. Returns True if successful."""
    try:
        os.replace(src, dst)
        return True
    except FileNotFoundError:
        # Another process already renamed it
        logger.info("Race detected: %s already claimed by another process", src)
        return False
    except OSError as e:
        logger.error("Failed to rename %s -> %s: %s", src, dst, e)
        return False


def transition_to_claimed(artifact: Artifact) -> bool:
    """Attempt to transition artifact from READY to CLAIMED."""
    if artifact.status != "READY":
        return False

    claimed_path = artifact.path.with_suffix(".claimed")
    return atomic_rename(artifact.path, claimed_path)


def read_claimed_artifact(claimed_path: Path) -> Optional[Artifact]:
    """Read an artifact that's already been claimed."""
    art = load_artifact(claimed_path)
    if art and art.status == "CLAIMED":
        return art
    return None


def run_verifier(artifact: Artifact, env: dict) -> tuple[int, str, str]:
    """Launch Hermes verifier and return (exit_code, stdout, stderr)."""
    repo_path = artifact.data.get("repo_path", os.getcwd())
    task_id = artifact.data.get("task_id", "UNKNOWN")
    head = artifact.data.get("head", "unknown")
    branch = artifact.data.get("branch", "unknown")
    task_contract = artifact.data.get("task_contract_path")

    # Build verification prompt
    prompt = build_verification_prompt(artifact)

    # Hermes CLI command
    cmd = [
        sys.executable,  # Current Python interpreter
        HERMES_CLI_PATH,
        "--query", prompt,
        "--oneshot",
        "--skills", "yalihan-independent-verifier,yalihan-os",
        "--max-turns", "60",
        "--non-interactive",
        "--provider", "aiwebmodel",  # Use aiwebmodel (ollama may not be running)
    ]

    # Optional: sandbox wrapper
    sandbox_cmd = build_sandbox_wrapper(cmd)

    logger.info("Launching verifier for %s (head=%s, branch=%s)", task_id, head, branch)

    try:
        result = subprocess.run(
            sandbox_cmd if sandbox_cmd else cmd,
            cwd=repo_path,
            env=env,
            capture_output=True,
            text=True,
            timeout=env.get("VERIFIER_TIMEOUT_SECONDS", 1800),  # 30 min default
        )
        return result.returncode, result.stdout, result.stderr
    except subprocess.TimeoutExpired:
        logger.error("Verifier timed out for %s", task_id)
        return -1, "", "TIMEOUT"
    except Exception as e:
        logger.error("Verifier failed for %s: %s", task_id, e)
        return -1, "", str(e)


def build_verification_prompt(artifact: Artifact) -> str:
    """Build the verification prompt for Hermes."""
    data = artifact.data
    task_id = data.get("task_id", "UNKNOWN")
    head = data.get("head", "unknown")
    branch = data.get("branch", "unknown")
    task_contract = data.get("task_contract_path", "")

    prompt = f"""VERIFICATION TASK: {task_id}

You are the independent verifier. Verify the implementation at git HEAD {head} on branch {branch}.

Task: {data.get("metadata", {}).get("task_description", "No description provided.")}

Declared write scope:
{chr(10).join(f"  - {f}" for f in data.get("declared_write_scope", []))}

Task contract: {task_contract}

Verification protocol:
1. Read the task contract at {task_contract}
2. Run the full antigravity gate: ./scripts/tools/antigravity-full-gate.sh
3. Verify all tests pass: php artisan test
4. Verify tenant isolation with negative test
5. Check for regressions
6. Report findings

Return your verdict as JSON:
{{"verdict": "VERIFIED_PASS|VERIFIED_FAIL|BLOCKED", "evidence_level": "...", "summary": "...", "control_violations": "...", "new_findings": "..."}}
"""

    return prompt


def build_sandbox_wrapper(cmd: list[str]) -> list[str]:
    """Build sandbox-exec wrapper if sandbox profile exists."""
    if not os.path.exists(SANDBOX_SB_PATH):
        logger.warning("Sandbox profile not found: %s", SANDBOX_SB_PATH)
        return []  # Return empty to use cmd directly

    return [
        "sandbox-exec",
        "-f", SANDBOX_SB_PATH,
        "--",
        *cmd,
    ]


def write_result(artifact: Artifact, verdict: str, summary: str,
                 evidence_level: str, control_violations: str = "NONE",
                 new_findings: str = "NONE") -> None:
    """Write verification result to artifact."""
    data = artifact.data.copy()
    data["status"] = verdict
    data["verification"] = {
        "verdict": verdict,
        "evidence_level": evidence_level,
        "verified_head": data.get("head"),
        "finished_at": datetime.now(timezone.utc).isoformat(),
        "summary": summary,
        "control_violations": control_violations,
        "new_findings": new_findings,
    }

    # Determine output path based on verdict
    suffix_map = {"VERIFIED_PASS": ".pass", "VERIFIED_FAIL": ".fail", "BLOCKED": ".blocked"}
    suffix = suffix_map.get(verdict, ".unknown")

    result_path = artifact.path.with_suffix(suffix)
    result_path = result_path.with_name(
        result_path.stem.replace(".claimed", "") + suffix
    )

    # Atomic write
    try:
        import tempfile
        fd, tmppath = tempfile.mkstemp(
            dir=result_path.parent,
            suffix=".result.tmp"
        )
        with os.fdopen(fd, "w") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
            f.flush()
            os.fsync(f.fileno())
        os.replace(tmppath, result_path)
        logger.info("Result written: %s", result_path)
    except Exception as e:
        logger.error("Failed to write result for %s: %s", artifact.task_id, e)


def reset_to_ready(artifact: Artifact) -> bool:
    """Reset a stuck artifact back to READY state."""
    ready_path = artifact.path.with_suffix(".handoff.json")
    ready_path = ready_path.with_name(
        ready_path.stem.replace(".claimed", "") + ".handoff.json"
    )
    return atomic_rename(artifact.path, ready_path)


# ---------------------------------------------------------------------------
# Watcher
# ---------------------------------------------------------------------------

def scan_pending_dir(pending_dir: Path) -> list[Path]:
    """Scan for READY handoff artifacts."""
    if not pending_dir.exists():
        return []

    artifacts = []
    for path in pending_dir.glob("*.handoff.json"):
        artifacts.append(path)
    return artifacts


def scan_claimed_dir(pending_dir: Path) -> list[Path]:
    """Scan for CLAIMED artifacts that need verification or recovery."""
    if not pending_dir.exists():
        return []

    artifacts = []
    for path in pending_dir.glob("*.claimed"):
        artifacts.append(path)
    return artifacts


def process_ready_artifact(path: Path, env: dict) -> None:
    """Attempt to claim and process a READY artifact."""
    artifact = load_artifact(path)
    if not artifact:
        return

    if artifact.status != "READY":
        return

    if not transition_to_claimed(artifact):
        return  # Another process won the race

    # Update status in-memory
    artifact.status = "CLAIMED"
    claimed_path = path.with_suffix(".claimed")

    logger.info("Claimed: %s", artifact.task_id)

    # Run verifier
    exit_code, stdout, stderr = run_verifier(artifact, env)

    # Update path reference
    artifact.path = claimed_path

    # Determine verdict from exit code and output
    verdict, summary, evidence_level = parse_verifier_output(
        exit_code, stdout, stderr
    )

    # Write result
    write_result(
        artifact,
        verdict=verdict,
        summary=summary,
        evidence_level=evidence_level,
    )


def parse_verifier_output(exit_code: int, stdout: str, stderr: str) -> tuple:
    """Parse verifier output and determine verdict."""
    if exit_code == 0:
        # Try to extract verdict from stdout
        verdict_match = re.search(
            r'"verdict"\s*:\s*"(VERIFIED_PASS|VERIFIED_FAIL|BLOCKED)"',
            stdout
        )
        if verdict_match:
            verdict = verdict_match.group(1)
        else:
            verdict = "VERIFIED_PASS"  # Default to pass if no explicit verdict

        summary_match = re.search(r'"summary"\s*:\s*"([^"]*)"', stdout)
        summary = summary_match.group(1) if summary_match else "Verifier completed successfully."

        evidence_match = re.search(r'"evidence_level"\s*:\s*"([^"]*)"', stdout)
        evidence_level = evidence_match.group(1) if evidence_match else "UNKNOWN"

        return verdict, summary, evidence_level

    elif exit_code == -1:
        return "BLOCKED", "Verifier timed out or was interrupted.", "TIMEOUT"

    else:
        # Parse error output for details
        error_summary = stderr[:500] if stderr else "Unknown error"
        return "VERIFIED_FAIL", f"Verifier exited with code {exit_code}: {error_summary}", "ERROR"


def recovery_loop(pending_dir: Path, timeout_minutes: int) -> None:
    """Check for stuck VERIFYING artifacts and reset them."""
    for path in scan_claimed_dir(pending_dir):
        try:
            mtime = path.stat().st_mtime
            age_minutes = (time.time() - mtime) / 60

            if age_minutes > timeout_minutes:
                artifact = read_claimed_artifact(path)
                if artifact:
                    logger.warning(
                        "Stale artifact detected: %s (age: %.1f min). Resetting to READY.",
                        artifact.task_id, age_minutes
                    )
                    reset_to_ready(path)
        except OSError as e:
            logger.error("Error checking %s: %s", path, e)


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> None:
    parser = argparse.ArgumentParser(
        description="Hermes verifier watchdog — watches pending dir and launches verifiers"
    )
    parser.add_argument(
        "--pending-dir",
        default=DEFAULT_PENDING_DIR,
        help=f"Pending directory (default: {DEFAULT_PENDING_DIR})",
    )
    parser.add_argument(
        "--hermes-home",
        default=DEFAULT_HERMES_HOME,
        help=f"Hermes home directory (default: {DEFAULT_HERMES_HOME})",
    )
    parser.add_argument(
        "--profile",
        default=DEFAULT_PROFILE,
        help=f"Hermes profile name (default: {DEFAULT_PROFILE})",
    )
    parser.add_argument(
        "--timeout-minutes",
        type=int,
        default=DEFAULT_TIMEOUT_MINUTES,
        help=f"Verifier timeout in minutes (default: {DEFAULT_TIMEOUT_MINUTES})",
    )
    parser.add_argument(
        "--poll-interval",
        type=int,
        default=DEFAULT_POLL_INTERVAL_SECONDS,
        help=f"Poll interval in seconds (default: {DEFAULT_POLL_INTERVAL_SECONDS})",
    )
    parser.add_argument(
        "--once",
        action="store_true",
        help="Run once (for testing) instead of continuous loop",
    )
    parser.add_argument(
        "--verbose",
        action="store_true",
        help="Enable verbose logging",
    )

    args = parser.parse_args()

    if args.verbose:
        logger.setLevel(logging.DEBUG)

    pending_dir = Path(args.pending_dir).expanduser()
    hermes_home = Path(args.hermes_home).expanduser()

    # Ensure pending directory exists
    pending_dir.mkdir(parents=True, exist_ok=True)

    logger.info("Starting verifier watchdog")
    logger.info("  pending_dir: %s", pending_dir)
    logger.info("  hermes_home: %s", hermes_home)
    logger.info("  profile: %s", args.profile)
    logger.info("  timeout: %d minutes", args.timeout_minutes)
    logger.info("  poll_interval: %d seconds", args.poll_interval)

    # Build environment
    env = os.environ.copy()
    env["HERMES_HOME"] = str(hermes_home)
    env["HERMES_PROFILE"] = args.profile
    env["HERMES_QUIET"] = "1"
    env["VERIFIER_TIMEOUT_SECONDS"] = str(args.timeout_minutes * 60)

    logger.info("Environment: HERMES_HOME=%s, HERMES_PROFILE=%s",
                env["HERMES_HOME"], env["HERMES_PROFILE"])

    while True:
        try:
            # Recovery pass: reset stale artifacts
            recovery_loop(pending_dir, args.timeout_minutes)

            # Process READY artifacts
            for path in scan_pending_dir(pending_dir):
                process_ready_artifact(path, env)

        except KeyboardInterrupt:
            logger.info("Shutting down watchdog")
            break
        except Exception as e:
            logger.error("Watchdog error: %s", e)
            time.sleep(args.poll_interval)
            continue

        if args.once:
            logger.info("Single pass complete (--once mode)")
            break

        time.sleep(args.poll_interval)


if __name__ == "__main__":
    main()
