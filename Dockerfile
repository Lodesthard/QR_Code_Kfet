FROM php:8.2-apache

# PHP extensions
RUN docker-php-ext-install mysqli

# Apache modules
RUN a2enmod rewrite ssl

# (Optionnel) dépendances utiles + cleanup
RUN apt-get update \
  && apt-get install -y --no-install-recommends ca-certificates \
  && rm -rf /var/lib/apt/lists/*

# PHP config
COPY ./php.ini "$PHP_INI_DIR/php.ini"

# SSL certs
RUN mkdir -p /etc/apache2/ssl
COPY ./ssl/*.pem /etc/apache2/ssl/

# Apache vhosts
COPY ./apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./apache/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite default-ssl

EXPOSE 80 443