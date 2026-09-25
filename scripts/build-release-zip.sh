#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="hkdev-shop-elements"
BUILD_DIR="$(mktemp -d)"
OUT_DIR="${ROOT}/dist"
ZIP_PATH="${OUT_DIR}/${SLUG}.zip"
PLUGIN_MAIN="${SLUG}.php"

mkdir -p "$OUT_DIR"
TARGET="${BUILD_DIR}/${SLUG}"
mkdir -p "$TARGET"

# Build from git-tracked files only so local/untracked folders (e.g. .cursor)
# never end up inside the release package.
git -C "$ROOT" archive --format=tar HEAD | tar -C "$TARGET" -xf -

# Remove repository-only files that should not ship in the plugin archive.
rm -rf \
	"${TARGET}/.github" \
	"${TARGET}/scripts" \
	"${TARGET}/.gitignore"

if [ ! -f "${TARGET}/${PLUGIN_MAIN}" ]; then
	echo "Error: ${PLUGIN_MAIN} is missing from release payload." >&2
	exit 1
fi

rm -f "$ZIP_PATH"
(
	cd "$BUILD_DIR"
	zip -rq "$ZIP_PATH" "$SLUG"
)

echo "Built ${ZIP_PATH}"
