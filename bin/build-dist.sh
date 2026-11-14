#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

SLUG="osm-map-elementor"
VERSION="$(grep -m1 OSM_MAP_VERSION constants.php | grep -o "[0-9.]*[0-9]")"

rm -rf "build/${SLUG}" "build/${SLUG}-${VERSION}.zip"
mkdir -p "build/${SLUG}"
rsync -a --delete --exclude-from=.distignore ./ "build/${SLUG}/"

# Source maps are excluded from the release, so drop the comments that point browsers at them.
find "build/${SLUG}" \( -name '*.js' -o -name '*.css' \) \
    -exec perl -i -ne 'print unless m{^\s*(//|/\*)# sourceMappingURL=}' {} +
(cd build && zip -rq "${SLUG}-${VERSION}.zip" "${SLUG}")

echo "build/${SLUG}-${VERSION}.zip"
