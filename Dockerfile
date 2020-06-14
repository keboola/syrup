FROM php:5.6
ENV DEBIAN_FRONTEND noninteractive
ENV COMPOSER_MEMORY_LIMIT 7g

RUN apt-get update -q \
  && apt-get install unzip git libmcrypt-dev -y --no-install-recommends \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mcrypt pdo_mysql

RUN pecl install xdebug-2.5.5 \
  && docker-php-ext-enable xdebug

WORKDIR /root

RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer

WORKDIR /code
