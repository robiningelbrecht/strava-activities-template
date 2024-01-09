FROM php:8.2-cli

RUN apt-get update && apt-get install -y zip unzip git curl

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer