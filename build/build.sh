#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"; VERSION="$(tr -d '\r\n' < "$ROOT/VERSION")"; DIST="$ROOT/dist"; WORK="$ROOT/build/.work"
rm -rf "$DIST" "$WORK"; mkdir -p "$DIST" "$WORK/component" "$WORK/package"; cp -R "$ROOT/component/." "$WORK/component/"
php -r '
$path = $argv[1];
$version = $argv[2];
$data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
$data["version"] = $version;
file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
' "$WORK/component/media/joomla.asset.json" "$VERSION"
find "$WORK" -type f -exec touch -t 198001010000 {} +
(cd "$WORK/component" && find . -type f -print0|sort -z|xargs -0 zip -X -q "$DIST/com_xdecaroorganizations_${VERSION}.zip")
cp "$ROOT/package/pkg_organizations.xml" "$WORK/package/pkg_organizations.xml"; cp "$ROOT/package/script.php" "$WORK/package/script.php"; cp "$DIST/com_xdecaroorganizations_${VERSION}.zip" "$WORK/package/com_xdecaroorganizations.zip"; find "$WORK/package" -type f -exec touch -t 198001010000 {} +
(cd "$WORK/package" && find . -type f -print0|sort -z|xargs -0 zip -X -q "$DIST/pkg_organizations_${VERSION}.zip")
(cd "$DIST" && sha256sum "com_xdecaroorganizations_${VERSION}.zip" "pkg_organizations_${VERSION}.zip">SHA256SUMS.txt)
