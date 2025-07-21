FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    default-mysql-client \ 
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zip unzip git curl vim locales jpegoptim optipng pngquant gifsicle \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . .

# Fix git safe directory
RUN git config --global --add safe.directory /var/www/html

# Set file & folder permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 755 public

# Install PHP dependencies (PRODUCTION)
# RUN composer install --no-dev --optimize-autoloader

# Laravel cache optimizations
# RUN php artisan config:cache \
#     && php artisan route:cache \
#     && php artisan view:cache \
#     && php artisan event:cache

# Expose port
EXPOSE 9000

# Run PHP FPM server
CMD ["php-fpm"]
# COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
# RUN chmod +x /usr/local/bin/docker-entrypoint.sh
# ENTRYPOINT ["docker-entrypoint.sh"]
