FROM php:8.2-apache

# System tools needed by labs
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl wget iputils-ping procps unzip git \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# PHP config
COPY php.ini /usr/local/etc/php/conf.d/lab.ini

# Enable mod_rewrite and AllowOverride for .htaccess
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy source
COPY src/ /var/www/html/

# Install Composer deps (Twig, Smarty)
RUN cd /var/www/html && composer install --no-dev --no-interaction --no-security-blocking 2>&1

# Upload dirs: writable by www-data, PHP-executable
RUN mkdir -p /var/www/html/uploads /var/www/html/uploads_r6 \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/uploads_r6 \
    && chmod 755 /var/www/html/uploads /var/www/html/uploads_r6

# Writable temp paths for blind labs
RUN touch /tmp/feedback.log /tmp/blind_out.txt /tmp/welcome_preview.txt /tmp/r4_note.php \
    && chmod 666 /tmp/feedback.log /tmp/blind_out.txt /tmp/welcome_preview.txt /tmp/r4_note.php

# Flag files — root-owned, read-only
COPY flags/ /flags/
RUN chmod 0555 /flags && find /flags -type f -exec chmod 0444 {} \; && chown -R root:root /flags

EXPOSE 80
