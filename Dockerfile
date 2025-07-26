FROM php:8.2-apache

# Enable Apache mod_rewrite (optional for frameworks like Laravel)
RUN a2enmod rewrite

# Copy PHP files into container
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
