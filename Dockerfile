# Use official PHP image with Apache
FROM php:8.2-apache

# Copy project files to the Apache web root
COPY . /var/www/html/

# Give Apache permission to read files
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 (default for web)
EXPOSE 80
