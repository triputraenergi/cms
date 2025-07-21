#!/bin/bash

set -e

echo "🔁 Connecting to DB at ${DB_HOST}:${DB_PORT}..."

# Wait until MySQL is accepting connections
until mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent; do
  echo "⏳ Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
  sleep 2
done

# Create the database if it doesn't exist
echo "✅ MySQL is ready. Creating database if not exists..."
mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Laravel setup
php artisan config:cache
php artisan migrate --force

# php artisan db:seed AdminUserSeeder --force

# Start PHP-FPM
exec php-fpm
