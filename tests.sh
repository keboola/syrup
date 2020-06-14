#!/usr/bin/env bash

echo "Starting tests" >&1
php --version \
    && composer --version \
    && /usr/local/bin/composer install \
    && docker-php-ext-enable xdebug
    && /code/bin/phpcs --standard=psr2 -n src tests \
    && /code/bin/phpunit --debug $@
