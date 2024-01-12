FROM php:8.2-cli

COPY ./php.ini "${PHP_INI_DIR}/conf.d/docker-php-config.ini"

RUN apt-get update && apt-get install -y zip unzip git curl

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer