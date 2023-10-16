#!/usr/bin/env bash
set -e

# Clone template
git clone https://github.com/robiningelbrecht/strava-activities-template.git

# Copy all files from template to this repo.
rm -Rf bin/* && mv -f strava-activities-template/bin/* bin/
rm -Rf config/* && mv -f strava-activities-template/config/* config/
rm -Rf migrations/* && mv -f strava-activities-template/migrations/* migrations/
rm -Rf public/* & mv -f strava-activities-template/public/* public/
rm -Rf src/* && mv -f strava-activities-template/src/* src/
rm -Rf templates/* && mv -f strava-activities-template/templates/* templates/
mv -f strava-activities-template/echart.js echart.js
mv -f strava-activities-template/composer.json composer.json
mv -f strava-activities-template/composer.lock composer.lock
mv -f strava-activities-template/package.json package.json
mv -f strava-activities-template/package-lock.json package-lock.json
mv -f strava-activities-template/vercel.json vercel.json

# Delete install files
rm -Rf files/install
# Delete test suite
rm -Rf tests

rm -Rf strava-activities-template

git add .
git status
git diff --staged --quiet || git commit -m"Updated template to latest version"

composer install --prefer-dist

# Install env file.
echo ENVIRONMENT=dev >> .env
echo DISPLAY_ERROR_DETAILS=1 >> .env
echo LOG_ERRORS=0 >> .env
echo LOG_ERROR_DETAILS=0 >> .env
echo DATABASE_NAME="database/db.strava" >> .env
echo STRAVA_CLIENT_ID=${{ secrets.STRAVA_CLIENT_ID }} >> .env
echo STRAVA_CLIENT_SECRET=${{ secrets.STRAVA_CLIENT_SECRET }} >> .env
echo STRAVA_REFRESH_TOKEN=${{ secrets.STRAVA_REFRESH_TOKEN }} >> .env
echo REPOSITORY_NAME=$GITHUB_REPOSITORY >> .env

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