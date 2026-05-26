FROM php:8.1-apache

# MySQL driver එක install කිරීම
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd

# Apache configuration (අවශ්‍ය නම්)
RUN a2enmod rewrite
