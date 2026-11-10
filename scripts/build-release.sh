#!/usr/bin/env sh
# Builds the installable archive dist/ticketboost-<version>.tar.bz2.
# The archive extracts to a `ticketboost/` directory ready for GLPI `plugins/`.
# Expects `pnpm build` and `scripts/locales-compile.sh` to have run.
# Usage: scripts/build-release.sh <version>
set -eu
version="${1:?usage: scripts/build-release.sh <version>}"
cd "$(dirname "$0")/.."

for required in public/js/ticketboost.js locales/es_ES.mo; do
  [ -f "$required" ] || { echo "$required missing: run make build" >&2; exit 1; }
done

stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT
dest="$stage/ticketboost"
mkdir -p "$dest/locales"

# Allowlist: only runtime files ship. vendor/ is excluded: the plugin has no runtime
# Composer dependencies (GLPI autoloads src/). If one is added, run
# `composer install --no-dev` in the release job and add vendor to this list.
for path in setup.php hook.php plugin.xml composer.json LICENSE README.md CHANGELOG.md src public ajax front templates; do
  [ -e "$path" ] && cp -R "$path" "$dest/"
done
cp locales/*.mo locales/*.po "$dest/locales/"
rm -f "$dest/public/.gitkeep"

mkdir -p dist
archive="dist/ticketboost-${version}.tar.bz2"
tar -C "$stage" -cjf "$archive" ticketboost
echo "Built $archive"
tar -tjf "$archive" | head -40
