FROM php:8.1-apache

# Install extensions needed for GLPI (MySQL)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Set permissions (will be overridden by volume, but keeps defaults sane)
RUN chown -R www-data:www-data /var/www/html
