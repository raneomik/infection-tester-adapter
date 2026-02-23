#!/usr/bin/env bash

availableSuites=(
  "TestCase"
  "Plain"
  "Function"
)

availableConfigs=(
  "common"
  "exclude"
  "multiSrc"
)

checkin () {
  if [ "$#" -ne 2 ]; then
    usage
    exit 1
  fi

  local suite=${1}
  local config=${2}

  if [[ ! " ${availableSuites[@]} " =~ " ${suite} " ]]; then
    echo "Error: Invalid suite '${suite}'."
    usage
    exit 1
  fi

  if [[ ! " ${availableConfigs[@]} " =~ " ${config} " ]]; then
    echo "Error: Invalid config '${config}'."
    usage
    exit 1
  fi
}

usage () {
	echo "Usage: $0 <suite> <config>"
	echo "  suite: The test suite to run (e.g., 'TestCase', 'Plain', 'Function')"
  echo "  config: The configuration to use (e.g. 'common', 'exclude', 'multiSrc')"
}

tputx () {
	test -x $(which tput) && tput "$@"
}

run () {
  local suite=${1}
  local config=${2}

  make infection suite=${suite} config=${config}
}

if [ ! -d "vendor" ]; then
  rm -f composer.lock
  composer install
fi

# Ensure directories for coverage output exist
mkdir -p var/infection/infection

checkin $*
run tests/$1 $2
