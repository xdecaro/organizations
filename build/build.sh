#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"; VERSION="$(tr -d '\r\n' < "$ROOT/VERSION")"; DIST="$ROOT/dist"; WORK="$ROOT/build/.work"
rm -rf "$DIST" "$WORK"; mkdir -p "$DIST" "$WORK/component" "$WORK/package"
cp -R "$ROOT/component/." "$WORK/component/"
( cd "$WORK/component" && find . -type f -print0 | sort -z | xargs -0 zip -X -q "$DIST/com_decaroorganizations_${VERSION}.zip" )
cp "$ROOT/package/pkg_decaroorganizations.xml" "$WORK/package/pkg_decaroorganizations.xml"; cp "$ROOT/package/script.php" "$WORK/package/script.php"; cp "$DIST/com_decaroorganizations_${VERSION}.zip" "$WORK/package/com_decaroorganizations.zip"
( cd "$WORK/package" && find . -type f -print0 | sort -z | xargs -0 zip -X -q "$DIST/pkg_decaroorganizations_${VERSION}.zip" )
( cd "$DIST" && sha256sum "com_decaroorganizations_${VERSION}.zip" "pkg_decaroorganizations_${VERSION}.zip" > SHA256SUMS.txt )
echo "Built Organizations by xdecaro $VERSION"
