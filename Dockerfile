FROM php:5.6
MAINTAINER Ondrej Hlavacek <ondrej.hlavacek@keboola.com>
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update -q \
  && apt-get install unzip git libmcrypt-dev -y --no-install-recommends \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mcrypt pdo_mysql

RUN pecl install xdebug \
  && docker-php-ext-enable xdebug

WORKDIR /root

RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer

WORKDIR /code