#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
AUDIT_SCRIPT="$ROOT_DIR/scripts/migration-audit.php"

if ! command -v php >/dev/null 2>&1; then
  echo "Error: php is not installed or not in PATH."
  exit 127
fi

if [[ "${1:-}" == "--strict" ]]; then
  php "$AUDIT_SCRIPT" --strict
else
  php "$AUDIT_SCRIPT"
fi
