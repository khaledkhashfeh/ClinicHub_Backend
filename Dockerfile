# Stage 1: Composer Install
FROM php:8.4-cli AS composer

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install necessary extensions for Composer
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    libpq-dev \
    && docker-php-ext-install -j$(nproc) pdo_pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy composer files first for better caching
COPY composer.json ./

# Install dependencies but skip scripts initially
RUN composer install --ignore-platform-reqs --no-scripts --no-autoloader --no-interaction --optimize-autoloader

# Copy entire project
COPY . .

# Run package discovery and dump autoloader
RUN composer dump-autoload --ignore-platform-reqs

# Publish L5-Swagger assets if the package is installed
RUN if [ -d "vendor/darkaonline/l5-swagger" ]; then \
        php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force; \
        php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --tag=swagger-ui --force; \
    fi

# Stage 2: Final Image
FROM php:8.4-cli

# Install necessary packages and extensions
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    netcat-openbsd \
    libpq-dev \
    && docker-php-ext-install -j$(nproc) pdo_pgsql mbstring exif pcntl bcmath gd \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www

# Copy application code
COPY . .

# Remove any existing vendor directory
RUN rm -rf vendor

# Copy vendor directory from composer stage
COPY --from=composer /var/www/vendor ./vendor

# Set proper permissions only for directories that need write access
RUN mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy the artisan command before creating the startup script
COPY --from=composer /var/www/artisan ./artisan

# Create startup script
RUN echo '#!/bin/bash\n\
    # Fix directory permissions\n\
    mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache\n\
    chown -R www-data:www-data storage bootstrap/cache\n\
    find storage bootstrap/cache -type d -exec chmod 775 {} \\;\n\
    find storage bootstrap/cache -type f -exec chmod 664 {} \\;\n\
    \n\
    # Run package discovery to ensure all packages are properly loaded\n\
    php artisan package:discover --ansi\n\
    \n\
    # Wait for database to be ready before running migrations\n\
    until nc -z db 5432; do\n\
      echo "Waiting for database..." && sleep 2\n\
    done\n\
    \n\
    # Run migrations\n\
    php artisan migrate --force\n\
    \n\
    # Start Laravel application with built-in PHP server\n\
    php artisan serve --host=0.0.0.0 --port=8000\n\
    ' > /start.sh \
    && chmod +x /start.sh

# Expose port 8000 (Laravel development server port)
EXPOSE 8000

# Use script to start the application
CMD ["/start.sh"]
