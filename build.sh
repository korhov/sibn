#!/usr/bin/env bash

cp app/composer.json docker/php-fpm/composer.json
cp app/composer.lock docker/php-fpm/composer.lock

tar -z -c --exclude ".git" --exclude docker --exclude ".idea" --exclude vendor -f docker/php-fpm/source.tar.gz -C ./app .

docker-compose up -d
