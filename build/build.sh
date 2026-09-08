#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"; VERSION="$(tr -d '\r\n' < "$ROOT/VERSION")"; DIST="$ROOT/dist"; WORK="$ROOT/build/.work"
rm -rf "$DIST" "$WORK"; mkdir -p "$DIST" "$WORK/component" "$WORK/package"
cp -R "$ROOT/component/." "$WORK/component/"
( cd "$WORK/component" && find . -type f -print0 | sort -z | xargs -0 zip -X -q "$DIST/com_xdecaroorganizations_${VERSION}.zip" )
cp "$ROOT/package/pkg_xdecaroorganizations.xml" "$WORK/package/pkg_xdecaroorganizations.xml"; cp "$ROOT/package/script.php" "$WORK/package/script.php"; cp "$DIST/com_xdecaroorganizations_${VERSION}.zip" "$WORK/package/com_xdecaroorganizations.zip"
( cd "$WORK/package" && find . -type f -print0 | sort -z | xargs -0 zip -X -q "$DIST/pkg_xdecaroorganizations_${VERSION}.zip" )
( cd "$DIST" && sha256sum "com_xdecaroorganizations_${VERSION}.zip" "pkg_xdecaroorganizations_${VERSION}.zip" > SHA256SUMS.txt )
echo "Built Organizations by xdecaro $VERSION"
