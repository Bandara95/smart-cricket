# 1. Base Image
FROM php:8.1-apache

# 2. අවශ්‍ය සියලුම ලයිබ්‍රරි සහ Extension ස්ථාපනය කිරීම
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    pkg-config \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Apache Rewrite Module සක්‍රීය කිරීම
RUN a2enmod rewrite

# 4. කෝඩ් එක කොපි කිරීම
COPY . /var/www/html/

# 5. අවසර ලබා දීම (Permissions)
RUN chown -R www-data:www-data /var/www/html

# 6. Apache ආරම්භ කිරීම
CMD ["apache2-foreground"]
