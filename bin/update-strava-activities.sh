#!/usr/bin/env bash
set -e

# Clone template
git clone https://github.com/robiningelbrecht/strava-activities-template.git --depth 1

# Copy all files from template to this repo.
## ROOT FILES
mv -f strava-activities-template/.gitignore .gitignore
mv -f strava-activities-template/tailwind.config.js tailwind.config.js
mv -f strava-activities-template/echart.js echart.js
mv -f strava-activities-template/composer.json composer.json
mv -f strava-activities-template/composer.lock composer.lock
mv -f strava-activities-template/package.json package.json
mv -f strava-activities-template/package-lock.json package-lock.json
mv -f strava-activities-template/vercel.json vercel.json
## SUB DIRECTORIES
rm -Rf config/* && mv -f strava-activities-template/config/* config/
rm -Rf migrations/* && mv -f strava-activities-template/migrations/* migrations/
rm -Rf public/* && mv -f strava-activities-template/public/* public/
rm -Rf src/* && mv -f strava-activities-template/src/* src/
rm -Rf templates/* && mv -f strava-activities-template/templates/* templates/
mv -f strava-activities-template/bin/console bin/console
mv -f strava-activities-template/bin/doctrine-migrations bin/doctrine-migrations
## HTML BUILD FILES
rm -Rf build/html/echarts/* && mv -f strava-activities-template/build/html/echarts/* build/html/echarts/
rm -Rf build/html/flowbite/* && mv -f strava-activities-template/build/html/flowbite/* build/html/flowbite/
mkdir -p build/html/leaflet && rm -Rf build/html/leaflet/* && mv -f strava-activities-template/build/html/leaflet/* build/html/leaflet/
mkdir -p build/html/data-table && rm -Rf build/html/data-table/* && mv -f strava-activities-template/build/html/data-table/* build/html/data-table/
mv -f strava-activities-template/build/html/dark-mode-toggle.js build/html/dark-mode-toggle.js
mv -f strava-activities-template/build/html/favicon.ico build/html/favicon.ico
mv -f strava-activities-template/build/html/placeholder.webp build/html/placeholder.webp
mv -f strava-activities-template/build/html/lazyload.min.js build/html/lazyload.min.js
mv -f strava-activities-template/build/html/router.js build/html/router.js


# Remove old files
rm -f build/html/searchable.js
rm -f build/html/sortable.js
rm -f build/html/activity-data-table.json
rm -f build/html/segment-data-table.json

# Make sure database and migration directories exist
mkdir -p database
mkdir -p migrations

# Delete install files
rm -Rf files/install
rm -Rf files/maps
# Delete test suite
rm -Rf tests
rm -Rf config/container_test.php
# Delete template again.
rm -Rf strava-activities-template

# Exit when only template update.
if [ "$1" == "--only-template" ]; then
  exit 0;
fi
if [ "$1" == "--template-only" ]; then
  exit 0;
fi

git add .
git status
git diff --staged --quiet || git commit -m"Updated template to latest version"

composer install --prefer-dist

# Run migrations.
rm -Rf database/db.strava-read
bin/doctrine-migrations migrate --no-interaction

# Update strava stats.
bin/console app:strava:import-data
bin/console app:strava:build-files

# Vacuum database
bin/console app:strava:vacuum

# Generate charts
npm ci
node echart.js

# Push changes
git add .
git status
git diff --staged --quiet || git commit -m"Updated strava activities"
git push
