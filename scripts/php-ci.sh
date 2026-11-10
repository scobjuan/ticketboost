#!/usr/bin/env bash
# PHP quality gate. Runs inside the official GLPI CI image
# (ghcr.io/glpi-project/githubactions-glpi-apache), locally through `make test-php`
# and in GitHub Actions, so both environments execute exactly the same steps.
#
# Env: DB_HOST (default testdb), COVERAGE_MIN (default 85), STEPS (default "all").
set -euo pipefail

PLUGIN_KEY=ticketboost
GLPI_DIR=/var/www/glpi
PLUGIN_DIR="$GLPI_DIR/plugins/$PLUGIN_KEY"
DB_HOST="${DB_HOST:-testdb}"
COVERAGE_MIN="${COVERAGE_MIN:-85}"
STEPS="${STEPS:-all}"
export COMPOSER_CACHE_DIR="${COMPOSER_CACHE_DIR:-/tmp/composer-cache}"
CONSOLE=(php "$GLPI_DIR/bin/console" --ansi --no-interaction)
if [[ "$(id -u)" == "0" ]]; then CONSOLE+=(--allow-superuser); fi

cd "$PLUGIN_DIR"
step() { printf '\n\033[0;33m== %s\033[0m\n' "$1"; }
wants() { [[ "$STEPS" == "all" || " $STEPS " == *" $1 "* ]]; }

step "Composer"
composer install --ansi --no-interaction --no-progress --prefer-dist

if wants lint; then
  step "PHP Parallel Lint"
  ../../vendor/bin/parallel-lint --colors --exclude ./node_modules/ --exclude ./vendor/ --no-progress .
  step "PHP-CS-Fixer"
  PHP_CS_FIXER_IGNORE_ENV=1 php ../../vendor/bin/php-cs-fixer check --config=.php-cs-fixer.php --no-interaction --show-progress=none
  step "PHPStan"
  php ../../vendor/bin/phpstan analyze --ansi --memory-limit=2G --no-interaction --no-progress
  step "Rector"
  php ../../vendor/bin/rector process --dry-run --ansi
  step "Licence headers (every PHP file carries tools/HEADER)"
  missing=$(find . -name '*.php' -not -path './vendor/*' -not -path './node_modules/*' -not -path './var/*' \
    -exec grep -L -F '@license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html' {} +)
  if [[ -n "$missing" ]]; then
    echo "Missing licence header in:"; echo "$missing"; exit 1
  fi
  echo "All PHP files carry the licence header"
fi

if wants test; then
  step "Install GLPI test database and plugin"
  for _ in $(seq 1 30); do
    php -r "exit(@mysqli_connect('$DB_HOST', 'root', '', 'glpi') ? 0 : 1);" && break
    sleep 2
  done
  "${CONSOLE[@]}" database:install --db-name=glpi --db-host="$DB_HOST" --db-user=root --db-password="" \
    --strict-configuration --reconfigure --force
  "${CONSOLE[@]}" plugin:install --username=glpi --force "$PLUGIN_KEY"
  "${CONSOLE[@]}" plugin:activate "$PLUGIN_KEY"

  step "PHPUnit with coverage (minimum ${COVERAGE_MIN}%)"
  php -d pcov.enabled=1 -d pcov.directory=. ../../vendor/bin/phpunit --colors=always \
    --coverage-text --coverage-clover=var/clover.xml
  php tools/coverage-check.php var/clover.xml "$COVERAGE_MIN"
fi
