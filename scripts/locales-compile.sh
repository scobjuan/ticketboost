#!/usr/bin/env sh
# Compiles locales/*.po into .mo files and derives regional variants, so every
# English and Spanish locale GLPI declares gets a translation.
set -eu
cd "$(dirname "$0")/.."

for po in locales/*.po; do
  msgfmt --check --output-file="${po%.po}.mo" "$po"
done

# Regional variants declared by GLPI 11/12 (src/Glpi/Locale/LanguageRegistry.php).
for variant in en_US; do
  cp locales/en_GB.mo "locales/${variant}.mo"
done
for variant in es_419 es_AR es_CL es_CO es_EC es_MX es_VE; do
  cp locales/es_ES.mo "locales/${variant}.mo"
done

echo "Locales compiled: $(ls locales/*.mo | wc -l) files"
