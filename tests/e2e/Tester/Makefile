# See https://tech.davis-hansson.com/p/make/
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

.DEFAULT_GOAL := help

.PHONY: help
help:
	@printf "\033[33mUsage:\033[0m\n  make TARGET\n\n\033[32m#\n# Commands\n#---------------------------------------------------------------------------\033[0m\n\n"
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | awk 'BEGIN {FS = ":"}; {printf "\033[33m%s:\033[0m%s\n", $$1, $$2}'

INFECTION=vendor/bin/infection

suite := 'tests'

.PHONY: check
check: 	 ## Runs all the checks
check: test

.PHONY: test
test:	 ## Executes the tests
test: tester infection

.PHONY: tester
tester: vendor/autoload.php
	vendor/bin/tester $(suite)

.PHONY: tester_coverage
tester_coverage: 	## Executes the tests with code coverage
tester_coverage: vendor/autoload.php
	XDEBUG_MODE=coverage vendor/bin/tester $(suite) \
		--coverage var/infection/infection/index.xml --coverage-src src \
		-o junit:var/infection/infection/junit.xml \

.PHONY: infection
infection: vendor/autoload.php
	$(INFECTION) \
		--threads=max \
		--show-mutations=0 \
		--with-uncovered \
		--test-framework-options=$(suite)
	@diff --ignore-all-space expected-output.txt var/infection.log

# Do install if there's no 'vendor'
vendor/autoload.php:
	composer install
	touch -c $@
	test -d vendor/infection/infection/src/StreamWrapper/ && rm -fr vendor/infection/infection/src/StreamWrapper/ && $(COMPOSER) dump-autoload || true

# If composer.lock is older than `composer.json`, do update,
# and touch composer.lock because composer not always does that
composer.lock: composer.json
	composer update
	touch -c $@
