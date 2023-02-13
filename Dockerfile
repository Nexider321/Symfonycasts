FROM php:7.4-apache
#Install git
RUN apt-get update \
    && apt-get install -y git libzip-dev
RUN docker-php-ext-install pdo pdo_mysql zip

#Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=. --filename=composer
RUN mv composer /usr/local/bin/
COPY ./ /var/www/html/
EXPOSE 80