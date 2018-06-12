FROM php:7.2-cli

RUN apt-get update \
  && apt-get install -y libzip-dev \
  && docker-php-ext-install zip \
  && docker-php-ext-install pcntl

WORKDIR /snidel
