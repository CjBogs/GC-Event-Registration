# Use PHP 8.1 with Apache
FROM php:8.1-apache

# Enable required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (optional, but useful for routing)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy your app code
COPY . /var/www/html/

# Set permissions (optional, helps with file uploads and logging)
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose port 80 (default Apache port)
EXPOSE 80
