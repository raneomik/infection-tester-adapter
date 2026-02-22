# See https://tech.davis-hansson.com/p/make/
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

.DEFAULT_GOAL := help

.PHONY: help
help:
	@printf "\033[33mUsage:\033[0m\n  make TARGET\n\n\033[32m#\n# Commands\n#---------------------------------------------------------------------------\033[0m\n\n"
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | awk 'BEGIN {FS = ":"}; {printf "\033[33m%s:\033[0m%s\n", $$1, $$2}'

# Rector
RECTOR=vendor/bin/rector


# PHP CS Fixer
PHP_CS_FIXER=./.tools/php-cs-fixer
PHP_CS_FIXER_URL="https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/download/$(shell cat ./.tools/phpcs-version)/php-cs-fixer.phar"

# PHPUnit
PHPUNIT=vendor/bin/phpunit
PHPUNIT_COVERAGE_CLOVER=--coverage-clover=build/logs/clover.xml
PHPUNIT_ARGS=--coverage-xml=build/logs/coverage-xml --log-junit=build/logs/junit.xml $(PHPUNIT_COVERAGE_CLOVER)

# PHPStan
PHPSTAN=vendor/bin/phpstan
PHPSTAN_ARGS=analyse src tests/phpunit -c .phpstan.neon

# Infection
INFECTION=./.tools/infection.phar
INFECTION_URL="https://github.com/infection/infection/releases/download/$(shell cat ./.tools/infection-version)/infection.phar"
MIN_MSI=68 #80
MIN_COVERED_MSI=68 #82
INFECTION_ARGS=--min-msi=$(MIN_MSI) --min-covered-msi=$(MIN_COVERED_MSI) --threads=max --no-interaction --show-mutations=0

.PHONY: all
all:	 ## Executes all checks
all: cs test

.PHONY: cs
cs:	 ## Apply CS fixes
cs: gitignore composer-validate rector php-cs-fixer

.PHONY: gitignore
gitignore:
	LC_ALL=C sort -u .gitignore -o .gitignore

.PHONY: composer-validate
composer-validate: vendor/autoload.php
	composer validate --strict
	@composer dumpautoload --strict-psr --dev -a -o

.PHONY: rector
rector:
	@$(RECTOR)

.PHONY: cs-lint
cs-lint: ## Run CS checks
cs-lint: php-cs-fixer-lint

.PHONY: php-cs-fixer
php-cs-fixer: $(PHP_CS_FIXER) vendor/autoload.php
	$(PHP_CS_FIXER) fix --verbose --diff

.PHONY: php-cs-fixer-lint
php-cs-fixer-lint: $(PHP_CS_FIXER) vendor/autoload.php
	$(PHP_CS_FIXER) fix --verbose --diff --dry-run

.PHONY: test
test:	 ## Executes the tests
test: phpstan test-unit infection test-e2e

.PHONY: phpstan
phpstan:
	$(PHPSTAN) $(PHPSTAN_ARGS) --no-progress

.PHONY: test-unit
test-unit: vendor/autoload.php
	XDEBUG_MODE=coverage $(PHPUNIT) $(PHPUNIT_ARGS)

.PHONY: test-e2e
test-e2e: vendor/autoload.php
	tests/e2e_tests

.PHONY: infection
infection: $(INFECTION)
	$(INFECTION) $(INFECTION_ARGS) $(args)

# Do install if there's no 'vendor'
vendor/autoload.php:
	composer install --prefer-dist

# If composer.lock is older than `composer.json`, do update,
# and touch composer.lock because composer not always does that
composer.lock: composer.json
	composer update
	touch -c $@

$(INFECTION): Makefile
	wget -q $(INFECTION_URL) --output-document=$(INFECTION)
	chmod a+x $(INFECTION)
	touch $@

$(PHP_CS_FIXER): Makefile
	wget -q $(PHP_CS_FIXER_URL) --output-document=$(PHP_CS_FIXER)
	chmod a+x $(PHP_CS_FIXER)
	touch $@
