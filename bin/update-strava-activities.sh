#!/usr/bin/env bash
set -e

# Clone template
git clone https://github.com/robiningelbrecht/strava-activities-template.git --depth 1

# Copy all files from template to this repo.
rm -Rf bin/* && mv -f strava-activities-template/bin/* bin/
rm -Rf config/* && mv -f strava-activities-template/config/* config/
rm -Rf migrations/* && mv -f strava-activities-template/migrations/* migrations/
rm -Rf public/* && mv -f strava-activities-template/public/* public/
rm -Rf src/* && mv -f strava-activities-template/src/* src/
rm -Rf templates/* && mv -f strava-activities-template/templates/* templates/
## Build asset files
mv -f strava-activities-template/build/html/echarts/echarts.min.js build/html/echarts/echarts.min.js
mv -f strava-activities-template/build/html/flowbite/flowbite.js build/html/flowbite/flowbite.js
mv -f strava-activities-template/build/html/flowbite/tailwind.css build/html/flowbite/tailwind.css
mv -f strava-activities-template/build/html/flowbite/tailwind.min.css build/html/flowbite/tailwind.min.css
mv -f strava-activities-template/build/html/dark-mode-toggle.js build/html/dark-mode-toggle.js
mv -f strava-activities-template/build/html/favicon.ico build/html/favicon.ico
mv -f strava-activities-template/build/html/placeholder.webp build/html/placeholder.webp
mv -f strava-activities-template/build/html/router.js build/html/router.js
mv -f strava-activities-template/build/html/sortable.min.js build/html/sortable.min.js

mv -f strava-activities-template/echart.js echart.js
mv -f strava-activities-template/composer.json composer.json
mv -f strava-activities-template/composer.lock composer.lock
mv -f strava-activities-template/package.json package.json
mv -f strava-activities-template/package-lock.json package-lock.json
mv -f strava-activities-template/vercel.json vercel.json

# Make sure database and migration directories exist
mkdir -p database
mkdir -p migrations

# Delete install files
rm -Rf files/install
# Delete test suite
rm -Rf tests
# Delete template again.
rm -Rf strava-activities-template

git add .
git status
git diff --staged --quiet || git commit -m"Updated template to latest version"

composer install --prefer-dist

# Run migrations.
./vendor/bin/doctrine-migrations migrate --no-interaction

# Update strava stats.
bin/console app:strava:import-data
bin/console app:strava:build-files

# Generate charts
npm ci
node echart.js

# Push changes
git add .
git status
git diff --staged --quiet || git commit -m"Updated strava activities"
git push