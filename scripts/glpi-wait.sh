#!/usr/bin/env sh
# Waits until each GLPI URL answers with its login page.
# Usage: scripts/glpi-wait.sh <url...>
set -eu
for url in "$@"; do
  printf 'Waiting for %s ' "$url"
  i=0
  until curl -fs -o /dev/null "$url/"; do
    i=$((i + 1))
    if [ "$i" -gt 120 ]; then
      echo 'timeout'
      exit 1
    fi
    printf '.'
    sleep 5
  done
  echo ' ready'
done
