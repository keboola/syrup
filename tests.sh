#!/usr/bin/env bash
/bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024
/sbin/mkswap /var/swap.1
/sbin/swapon /var/swap.1

echo "Starting tests" >&1
php --version \
    && composer --version \
    && php -d memory_limit=256M /usr/local/bin/composer install \
    && /code/bin/phpcs --standard=psr2 -n src tests \
    && /code/bin/phpunit --debug $@
