#!/bin/bash

# Exit if no commit message is provided
if [ -z "$1" ]; then
  echo "❌ Please provide a commit message."
  exit 1
fi

# Get the current branch name
branchname=$(git rev-parse --abbrev-ref HEAD)

# Git commands
git add .
git commit -m "$1"
git push origin "$branchname"
