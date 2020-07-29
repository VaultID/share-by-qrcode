#
# Use this dockerfile to run api-tools.
#
# Start the server using docker-compose:
#
#   docker-compose build
#   docker-compose up
#
# You can install dependencies via the container:
#
#   docker-compose run api-tools composer install
#
# You can manipulate dev mode from the container:
#
#   docker-compose run api-tools composer development-enable
#   docker-compose run api-tools composer development-disable
#   docker-compose run api-tools composer development-status
#
# OR use plain old docker 
#
#   docker build -f Dockerfile-dev -t api-tools .
#   docker run -it -p "8080:80" -v $PWD:/var/www api-tools
#
FROM php:7.2-apache

RUN mkdir /etc/apache2/cert

RUN apt-get update \
 && apt-get install -y git zlib1g-dev libmcrypt-dev libmhash-dev libicu-dev g++ \
    libfreetype6-dev libjpeg62-turbo-dev libmcrypt-dev \
 && pecl install \
 && docker-php-ext-configure intl \
 && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
 && docker-php-ext-install zip pdo_mysql hash intl gd \
 && docker-php-ext-enable hash

RUN a2enmod rewrite \
 && a2enmod ssl \
 && a2enmod headers \
 && a2ensite default-ssl.conf \
 && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf \
 && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/default-ssl.conf \
 && sed -i 's!#SSLCertificateChainFile!SSLCertificateChainFile!g' /etc/apache2/sites-available/default-ssl.conf \
 && sed -i 's!/etc/ssl/certs/ssl-cert-snakeoil.pem!/etc/apache2/cert/cert.pem!g' /etc/apache2/sites-available/default-ssl.conf \
 && sed -i 's!/etc/ssl/private/ssl-cert-snakeoil.key!/etc/apache2/cert/cert.key!g' /etc/apache2/sites-available/default-ssl.conf \
 && sed -i 's!/etc/apache2/ssl.crt/server-ca.crt!/etc/apache2/cert/AC.pem!g' /etc/apache2/sites-available/default-ssl.conf \
 && mv /var/www/html /var/www/public \
 && curl -sS https://getcomposer.org/installer \
  | php -- --install-dir=/usr/local/bin --filename=composer \
 && echo "AllowEncodedSlashes On" >> /etc/apache2/apache2.conf

# Time Zone
RUN echo "date.timezone=America/Sao_Paulo" > $PHP_INI_DIR/conf.d/date_timezone.ini

# Cria grupo 1000
RUN getent group 1000 || groupadd web -g 1000

# Cria usuario 1000
RUN getent passwd 1000 || adduser --uid 1000 --gid 1000 --disabled-password --gecos "" web

RUN usermod -a -G web www-data

ENV APACHE_RUN_USER=web

RUN echo 'TraceEnable off' >> /etc/apache2/apache2.conf

WORKDIR /var/www
