#!/usr/bin/env bash

# Prints the number of arguments received, followed by each argument.
echo "Count: $#"
for arg in "$@"; do
  echo "[$arg]"
done
