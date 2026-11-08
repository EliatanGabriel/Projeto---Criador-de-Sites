# syntax=docker/dockerfile:1
# Stage 1: Install PHP dependencies with Composer
FROM docker.io/library/composer:2.8 AS composer

WORKDIR /tmp/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Stage 2: Build frontend with Node.js
FROM docker.io/library/node:20-alpine AS web

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 3: Production PHP-FPM image
FROM docker.io/library/php:8.4-fpm-alpine AS app

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    postgresql-client \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    gettext \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        mbstring \
        zip \
        bcmath \
        xml

# Set working directory
WORKDIR /app

# Copy application code (excluding dockerignore'd files)
COPY . .

# Copy composer dependencies from composer stage
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=composer /tmp/composer/vendor ./vendor

# Copy built frontend assets from web stage
COPY --from=web /app/public/build ./public/build

# Configure PHP-FPM
# Do NOT copy .env - use environment variables from runtime
# Do NOT copy vendor deps or node_modules

# Set up directories that Laravel needs to write to
RUN mkdir -p /app/storage/framework/sessions /app/storage/framework/cache /app/storage/framework/views /app/storage/logs /app/storage/app/private /app/bootstrap/cache && \
    chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Configure nginx to listen on port specified by PORT env var
# The entrypoint will template the nginx config with $PORT

# Expose the port
EXPOSE 8000

# Set environment variables for Laravel production
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production \
    APP_DEBUG=false

COPY docker/nginx.conf /etc/nginx/http.d/app.conf.template
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf

# Set entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]