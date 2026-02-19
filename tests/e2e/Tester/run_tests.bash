#!/usr/bin/env bash

tputx () {
	test -x $(which tput) && tput "$@"
}

run () {
    local INFECTION=${1}
    local PHPARGS=${2}

    if [ "$DRIVER" = "phpdbg" ]
    then
        phpdbg $PHPARGS -qrr $INFECTION
    else
        php $PHPARGS $INFECTION
    fi
}

cd "$(dirname "$0")"

if [ ! -d "vendor" ]; then
    rm -f composer.lock
    composer install
fi

# Ensure directories for coverage output exist
mkdir -p var/infection/infection

printf 'Plain suite'
run "vendor/bin/infection --with-uncovered --threads=max --test-framework-options=tests/Plain"
diff -w expected-output.txt var/infection.log

printf 'FunctionTest suite'
run "vendor/bin/infection --with-uncovered --threads=max --test-framework-options=tests/FunctionTest"
diff -w expected-output.txt var/infection.log

printf 'TestCase suite'
run "vendor/bin/infection --with-uncovered --threads=max --test-framework-options=tests/TestCase"
diff -w expected-output.txt var/infection.log
