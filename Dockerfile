FROM php:8.1-apache

# Enable pdo_mysql extension
RUN docker-php-ext-install pdo_mysql pdo

# Enable Apache mod_rewrite if needed
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html
