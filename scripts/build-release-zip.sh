#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="hkdev-shop-elements"
BUILD_DIR="$(mktemp -d)"
OUT_DIR="${ROOT}/dist"

mkdir -p "$OUT_DIR"
TARGET="${BUILD_DIR}/${SLUG}"
mkdir -p "$TARGET"

tar -C "$ROOT" \
	--exclude='./.git' \
	--exclude='./.github' \
	--exclude='./dist' \
	--exclude='./scripts' \
	-cf - . | tar -C "$TARGET" -xf -

(
	cd "$BUILD_DIR"
	zip -rq "${OUT_DIR}/${SLUG}.zip" "$SLUG"
)

echo "Built ${OUT_DIR}/${SLUG}.zip"
