FROM php:8.3-fpm-alpine

# 🏗️ Install system dependencies
RUN apk add --update --no-cache \
    build-base \
    autoconf \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    unzip \
    git \
    curl \
    vim \
    mysql-client \
    redis \
    icu-dev \
    libxml2-dev \
    libzip-dev \
    nodejs \
    npm

# 📦 Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    bcmath \
    fileinfo \
    xml \
    intl \
    gd \
    pcntl \
    zip

# 📦 Install PECL extensions (Redis)
RUN pecl install redis \
    && docker-php-ext-enable redis

# 🔧 Configure PHP
COPY docker/php/php.ini /usr/local/etc/php/
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# 🎯 Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 👥 Create app user
RUN addgroup -g 1000 appuser && \
    adduser -D -u 1000 -G appuser appuser && \
    mkdir -p /app/storage/framework/{sessions,views,cache} /app/storage/logs /app/bootstrap/cache && \
    chown -R appuser:appuser /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# 📂 Set working directory
WORKDIR /app

# 📥 Copy application files
COPY --chown=appuser:appuser . .

# 📚 Install dependencies
RUN composer install --no-interaction --optimize-autoloader

# 🔐 Set permissions
RUN chown -R appuser:appuser /app && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# 👤 Switch to app user
USER appuser

# 🚀 Expose port
EXPOSE 8000

# 💫 Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000/api/v1/health || exit 1

# ▶️ Start PHP-FPM
CMD ["php-fpm"]
