FROM php:8-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN apt-get update && apt-get upgrade -y

RUN a2enmod rewrite
COPY ./php.ini "$PHP_INI_DIR/"

COPY ./apache/000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80