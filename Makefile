compose=docker compose

dc:
	@${compose} -f .docker/docker-compose.yml $(cmd)

dcr:
	@make dc cmd="run --rm php-cli $(cmd)"

stop:
	@make dc cmd="stop"

up:
	@make dc cmd="up -d"

build-containers:
	@make dc cmd="build $(arg)"

down:
	@make dc cmd="down"

## Helpers.
composer:
	@make dcr cmd="composer $(arg)"

console:
	@make dcr cmd="bin/console $(arg)"

migrate-diff:
	@make dcr cmd="bin/doctrine-migrations diff"

migrate-run:
	@make dcr cmd="bin/doctrine-migrations migrate"

phpunit:
	@make dcr cmd="vendor/bin/phpunit $(arg)"

phpstan:
	vendor/bin/phpstan --memory-limit=1G $(arg)

csfix:
	vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php

cleanup-snapshots:
	find . -name __snapshots__ -type d -prune -exec rm -rf {} \;

git-add:
	make csfix && git add .

## App shortcuts
app-import-strava-data:
	@make console arg="app:strava:import-data"

app-build-strava-files:
	@make console arg="app:strava:build-files"

app-run-update:
	@make dcr cmd="bin/update-strava-activities.sh $(arg)"

app-flowbite-build:
	npm run flowbite:build