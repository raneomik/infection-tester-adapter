#!/usr/bin/env bash

tputx () {
	test -x $(which tput) && tput "$@"
}

run () {
    local suite=${1}
    local PHPARGS=${2}

    php $PHPARGS vendor/bin/infection \
      --no-ansi \
      --threads=max \
      --with-uncovered \
      --show-mutations=0 \
      --test-framework-options=${suite}
}

cd "$(dirname "$0")"

if [ ! -d "vendor" ]; then
    rm -f composer.lock
    composer install
fi

# Ensure directories for coverage output exist
mkdir -p var/infection/infection

run "tests/Plain"
diff -w expected-output.txt var/infection.log

run "tests/FunctionTest"
diff -w expected-output.txt var/infection.log

run "tests/TestCase"
diff -w expected-output.txt var/infection.log
