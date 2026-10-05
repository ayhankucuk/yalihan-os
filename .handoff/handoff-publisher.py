#!/usr/bin/env python3
"""
handoff-publisher.py — Cline → Hermes artifact publisher

Publishes a READY handoff artifact for Hermes verifier consumption.
Used by Cline at task completion (via .clinerules instruction).

Usage:
    python handoff-publisher.py <task_id> [--repo <path>] [--branch <branch>]

Example (in .clinerules):
    python3 .handoff/handoff-publisher.py CLINE_36_INTL --repo /Users/macbookpro/repos/yalihan-os
"""

import argparse
import json
import os
import subprocess
import sys
import tempfile
from datetime import datetime, timezone
from pathlib import Path


def run_git(repo_path: str, *args: str) -> str:
    result = subprocess.run(
        ["git", "-C", repo_path] + list(args),
        capture_output=True,
        text=True,
        check=True,
    )
    return result.stdout.strip()


def get_git_info(repo_path: str) -> dict:
    """Gather git metadata for the current HEAD."""
    try:
        head_full = run_git(repo_path, "rev-parse", "HEAD")
        head_short = run_git(repo_path, "rev-parse", "--short", "HEAD")
        branch = run_git(repo_path, "rev-parse", "--abbrev-ref", "HEAD")
        status = run_git(repo_path, "status", "--porcelain")
        diff = run_git(repo_path, "diff", "--stat", "HEAD")
        return {
            "head_full": head_full,
            "head_short": head_short,
            "branch": branch,
            "has_uncommitted_changes": bool(status.strip()),
            "diff_summary": diff,
        }
    except subprocess.CalledProcessError as e:
        print(f"[handoff-publisher] git error: {e.stderr}", file=sys.stderr)
        raise


def build_artifact(
    task_id: str,
    repo_path: str,
    declared_write_scope: list[str],
    task_contract_path: str | None,
    metadata: dict | None,
) -> dict:
    """Build the handoff artifact structure."""
    git_info = get_git_info(repo_path)
    now = datetime.now(timezone.utc).isoformat()

    return {
        "version": "1.0",
        "task_id": task_id,
        "status": "READY",
        "source": "cline",
        "source_session": os.environ.get("CLINE_SESSION_ID", "unknown"),
        "implementation_complete": True,
        "repo_path": os.path.abspath(repo_path),
        "head": git_info["head_short"],
        "branch": git_info["branch"],
        "head_full": git_info["head_full"],
        "has_uncommitted_changes": git_info["has_uncommitted_changes"],
        "diff_summary": git_info["diff_summary"],
        "declared_write_scope": declared_write_scope,
        "task_contract_path": task_contract_path,
        "snapshot_manifest": {
            "type": "git",
            "description": f"git HEAD at READY publication ({git_info['head_short']})",
        },
        "created_at": now,
        "created_by": "cline",
        "verification": None,
        "metadata": metadata or {},
    }


def atomic_write_json(filepath: str, data: dict) -> None:
    """Atomically write a JSON file using os.replace() (atomic on POSIX)."""
    dirname = os.path.dirname(filepath) or "."
    os.makedirs(dirname, exist_ok=True)

    fd, tmppath = tempfile.mkstemp(dir=dirname, suffix=".handoff.tmp")
    try:
        with os.fdopen(fd, "w") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
            f.flush()
            os.fsync(f.fileno())
        os.replace(tmppath, filepath)
        print(f"[handoff-publisher] Published: {filepath}")
    except Exception:
        if os.path.exists(tmppath):
            os.unlink(tmppath)
        raise


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Publish Cline → Hermes handoff artifact"
    )
    parser.add_argument("task_id", help="Task identifier (e.g. CLINE_36_INTL)")
    parser.add_argument(
        "--repo",
        default=os.environ.get("GIT_WORK_TREE", os.getcwd()),
        help="Repository path",
    )
    parser.add_argument(
        "--branch",
        default=None,
        help="Override git branch (auto-detected if omitted)",
    )
    parser.add_argument(
        "--scope",
        nargs="+",
        default=[],
        help="Files modified by this task",
    )
    parser.add_argument(
        "--contract",
        default=None,
        help="Path to task contract markdown",
    )
    parser.add_argument(
        "--metadata",
        default=None,
        help="JSON string of additional metadata",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Print artifact without writing",
    )
    parser.add_argument(
        "--pending-dir",
        default=None,
        help="Override pending directory (default: ~/.hermes/profiles/yalihan-verifier/pending/)",
    )

    args = parser.parse_args()

    pending_dir = args.pending_dir or os.path.expanduser(
        "~/.hermes/profiles/yalihan-verifier/pending/"
    )

    metadata = {}
    if args.metadata:
        try:
            metadata = json.loads(args.metadata)
        except json.JSONDecodeError as e:
            print(f"[handoff-publisher] Invalid --metadata JSON: {e}", file=sys.stderr)
            sys.exit(1)

    if args.branch:
        metadata["branch_override"] = args.branch

    artifact = build_artifact(
        task_id=args.task_id,
        repo_path=args.repo,
        declared_write_scope=args.scope,
        task_contract_path=args.contract,
        metadata=metadata,
    )

    if args.dry_run:
        print(json.dumps(artifact, indent=2, ensure_ascii=False))
        return

    timestamp = datetime.now(timezone.utc).strftime("%Y%m%d_%H%M%S")
    filename = f"{args.task_id}_{artifact['head']}_{timestamp}.handoff.json"
    filepath = os.path.join(pending_dir, filename)

    os.makedirs(pending_dir, exist_ok=True)
    atomic_write_json(filepath, artifact)
    print(f"[handoff-publisher] Artifact: {filepath}")
    print(f"[handoff-publisher] Git HEAD: {artifact['head']} ({artifact['head_full']})")
    print(f"[handoff-publisher] Status: {artifact['status']}")


if __name__ == "__main__":
    main()
