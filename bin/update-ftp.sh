#!/usr/bin/env bash
set -e

# Make sure database and migration directories exist
mkdir -p database
mkdir -p migrations
# Run migrations.
bin/doctrine-migrations migrate --no-interaction

bin/console app:strava:update-ftp $1 $2

# Push changes
git add .
git status
git diff --staged --quiet || git commit -m"Updated FTP"
git push