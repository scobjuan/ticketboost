#!/usr/bin/env sh
# Installs and activates the plugin in the running GLPI containers.
# Usage: scripts/glpi-plugin.sh <compose command> <service...>
set -eu
compose="$1"
shift
for service in "$@"; do
  echo "== $service"
  $compose exec -T "$service" php bin/console --no-interaction plugin:install --username=glpi --force ticketboost
  $compose exec -T "$service" php bin/console --no-interaction plugin:activate ticketboost
  $compose exec -T "$service" php bin/console --no-interaction cache:clear >/dev/null 2>&1 || true
done
