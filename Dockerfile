FROM php:8.1-apache

# ඔබේ කෝඩ් එක සර්වර් එකේ ප්ලේස් එකට කොපි කිරීම
COPY . /var/www/html/

# MySQL driver සහ අනෙකුත් අවශ්‍යතා ස්ථාපනය කිරීම
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd

# Apache rewrite module සක්‍රීය කිරීම
RUN a2enmod rewrite

# අවසානයේ Apache පනගැන්වීම
CMD ["apache2-foreground"]
