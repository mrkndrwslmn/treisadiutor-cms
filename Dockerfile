# Use the official PHP image with Apache
FROM php:8.2-apache

# Install mysqli and other PHP extensions
RUN docker-php-ext-install mysqli

# Copy project files into the Apache web root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80
