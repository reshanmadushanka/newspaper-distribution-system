# =============================================================================
# Stage 1: Build Frontend Assets (Node.js)
# =============================================================================
FROM node:22-alpine AS node-builder

WORKDIR /app

# Build-time env vars for Vite (VITE_* vars get baked into the JS bundle)
ARG VITE_APP_NAME="Newspaper Distribution System"
ENV VITE_APP_NAME=${VITE_APP_NAME}

# Copy .npmrc first so its settings are available to npm ci
COPY .npmrc* ./

# Install ALL dependencies (including devDependencies needed for Vite build)
# IMPORTANT: Do NOT set NODE_ENV=production here — npm skips devDeps when it's set
COPY package.json package-lock.json ./
RUN npm ci

# Copy source files needed for the build
COPY resources/ ./resources/
COPY vite.config.js ./
COPY jsconfig.json ./
COPY components.json ./
COPY public/ ./public/

# Build production assets — set NODE_ENV=production inline only for this step
RUN NODE_ENV=production npm run build

# =============================================================================
# Stage 2: PHP Application (Apache)
# =============================================================================
# Use PHP 8.4 — required by Symfony 8.0.x packages in composer.lock
FROM php:8.4-apache AS app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libicu-dev \
    libgmp-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Configure Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Add optimized PHP/OPcache settings
COPY .docker/php.ini $PHP_INI_DIR/conf.d/99-custom.ini

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install PHP dependencies (no dev)
COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev

# Copy the rest of the application source
COPY . .

# Create .env so Laravel/artisan can bootstrap.
# All real runtime config comes from docker-compose environment vars (which override .env).
# Use cp .env.example as a template, fall back to touch if .env.example is unavailable.
RUN cp .env.example .env 2>/dev/null || touch .env

# Copy built frontend assets from Stage 1
COPY --from=node-builder /app/public/build ./public/build

# Set storage and cache permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy Apache virtual host config
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Run Laravel bootstrap commands at startup via entrypoint
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
