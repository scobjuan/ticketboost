#!/usr/bin/env sh
# Seeds the e2e data (users tech1 and tech2, six tickets) in the running GLPI containers.
# Usage: scripts/glpi-seed.sh <compose command> <service...>
set -eu
compose="$1"
shift
for service in "$@"; do
  echo "== $service"
  $compose exec -T --user www-data "$service" php /var/www/glpi/plugins/ticketboost/e2e/seed/seed.php
done
