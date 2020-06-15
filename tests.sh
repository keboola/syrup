#!/usr/bin/env bash

echo "Starting tests" >&1
php --version \
    && composer --version \
    && php -d memory_limit=-1 /usr/local/bin/composer install \
    && /code/bin/phpcs --standard=psr2 -n src tests \
    && /code/bin/phpunit --debug $@
