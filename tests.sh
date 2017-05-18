#!/usr/bin/env bash
echo "Starting tests" >&1
php --version \
    && composer --version \
    && composer install \
    && /code/bin/phpcs --standard=psr2 -n src tests \
    && /code/bin/phpunit --debug $@
