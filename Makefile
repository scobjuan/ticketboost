# Developer entry points. Everything heavy runs in containers; see compose.yaml.
# Override the compose tool with: make up COMPOSE="docker compose"
COMPOSE ?= podman-compose
GLPI_SERVICES ?= glpi11 glpi12
GLPI_URLS ?= http://localhost:8011 http://localhost:8012
PLAYWRIGHT_VERSION ?= $(shell node -p "require('@playwright/test/package.json').version" 2>/dev/null)

.PHONY: help install up down reset logs install-plugin build locales lint lint-ts lint-php \
	typecheck fix-php test test-ts test-php test-php11 test-php12 e2e e2e-ui e2e-container size agents-check docs \
	check release-dry clean

help: ## List targets
	@grep -E '^[a-z0-9-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-16s %s\n", $$1, $$2}'

install: ## Install Node dependencies and git hooks
	pnpm install

up: ## Start MariaDB, GLPI 11 (:8011) and GLPI 12 (:8012)
	$(COMPOSE) up -d db $(GLPI_SERVICES)
	./scripts/glpi-wait.sh $(GLPI_URLS)

down: ## Stop the environment (keeps data)
	$(COMPOSE) --profile tools down

reset: ## Stop the environment and delete all data volumes
	$(COMPOSE) --profile tools down -v

logs: ## Follow GLPI logs
	$(COMPOSE) logs -f $(GLPI_SERVICES)

build: locales ## Build the frontend bundle and compile locales
	pnpm build

locales: ## Compile .po files into .mo files
	./scripts/locales-compile.sh

install-plugin: build ## Install and activate the plugin in the running GLPI containers
	./scripts/glpi-plugin.sh "$(COMPOSE)" $(GLPI_SERVICES)

lint: lint-ts lint-php ## All linters

lint-ts: ## ESLint, Prettier and TypeScript
	pnpm lint
	pnpm format:check
	pnpm typecheck

typecheck: ## TypeScript type check
	pnpm typecheck

lint-php: ## PHP lint, php-cs-fixer, PHPStan and Rector (GLPI 12 image)
	$(COMPOSE) --profile tools run --rm -e STEPS=lint tools12

fix-php: ## Apply Rector and php-cs-fixer fixes (GLPI 12 image)
	$(COMPOSE) --profile tools run --rm --entrypoint bash tools12 -c 'composer install -q --no-interaction >/dev/null 2>&1; php ../../vendor/bin/rector process; PHP_CS_FIXER_IGNORE_ENV=1 php ../../vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --no-interaction --show-progress=none'

test: test-ts test-php ## All unit tests with coverage gates

test-ts: ## Vitest with coverage gate (90%)
	pnpm test:coverage

test-php: test-php11 test-php12 ## PHPUnit with coverage gate (85%) on GLPI 11 and 12

test-php11:
	$(COMPOSE) --profile tools run --rm -e STEPS=test tools11

test-php12:
	$(COMPOSE) --profile tools run --rm -e STEPS=test tools12

e2e: install-plugin ## Playwright against the running GLPI containers
	pnpm e2e

e2e-ui: ## Playwright UI mode
	pnpm e2e --ui

e2e-container: install-plugin ## Playwright inside the official image (hosts without Playwright system deps)
	podman run --rm --network host -v "$(CURDIR):/work:Z" -w /work -e CI \
		mcr.microsoft.com/playwright:v$(PLAYWRIGHT_VERSION)-noble node node_modules/@playwright/test/cli.js test $(ARGS)

size: build ## Bundle size budget
	pnpm size

agents-check: ## Project rule guards (emojis, attribution, memory caps, translations)
	pnpm agents:check

docs: ## Build the documentation site (strict)
	podman run --rm -v "$(CURDIR):/docs:Z" docker.io/squidfunk/mkdocs-material:9.8.1 build --strict

check: agents-check lint test size docs ## Everything CI runs, except e2e

release-dry: build ## Build the release archive locally without publishing
	./scripts/build-release.sh 0.0.0-dev

clean: ## Remove build outputs
	rm -rf public/js public/css coverage var site dist locales/*.mo playwright-report test-results
