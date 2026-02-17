#!/usr/bin/env bash

set -e

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

set -e pipefail

if [ ! -d "vendor" ]; then
    rm -f composer.lock
    composer install
fi

# Ensure directories for coverage output exist
mkdir -p var/infection/infection

# First, run Infection once with --debug + --with-uncovered to generate coverage and junit files and keep them
run "vendor/bin/infection --with-uncovered"

diff -w expected-output.txt var/infection.log
