#!/bin/bash
# install-watchdog.sh — Install verifier watchdog as launchd user agent
# Run once to install, and again to uninstall

set -e

PLIST_NAME="ai.yalihan-os.verifier-watchdog.plist"
PLIST_SOURCE="$(dirname "$0")/$PLIST_NAME"
PLIST_TARGET="$HOME/Library/LaunchAgents/$PLIST_NAME"
LOG_DIR="$HOME/.hermes/profiles/yalihan-verifier/logs"

echo "=== Verifier Watchdog Installer ==="

# Create log directory
echo "Creating log directory: $LOG_DIR"
mkdir -p "$LOG_DIR"

# Check plist exists
if [ ! -f "$PLIST_SOURCE" ]; then
    echo "✗ Plist not found: $PLIST_SOURCE"
    exit 1
fi

# Check if already installed
if launchctl list | grep -q "ai.yalihan-os.verifier-watchdog"; then
    echo "⚠ Watchdog already installed"
    echo ""
    echo "To restart: launchctl unload $PLIST_TARGET && launchctl load $PLIST_TARGET"
    echo "To uninstall: ./install-watchdog.sh --uninstall"
    exit 0
fi

# Copy plist
echo "Installing plist to: $PLIST_TARGET"
cp "$PLIST_SOURCE" "$PLIST_TARGET"

# Load agent
echo "Loading launchd agent..."
launchctl load "$PLIST_TARGET"

# Verify
echo ""
echo "Verifying..."
sleep 1
if launchctl list | grep -q "ai.yalihan-os.verifier-watchdog"; then
    echo "✓ Watchdog installed and running"
    echo ""
    echo "Logs: tail -f $LOG_DIR/watchdog.log"
    echo "Status: launchctl list | grep verifier"
else
    echo "✗ Installation failed. Check logs: $LOG_DIR/watchdog.err"
fi
