# ═══════════════════════════════════════════════════════════════
# Stage 1: Composer dependencies
# ═══════════════════════════════════════════════════════════════
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install \
    --no-interaction \
    --no-scripts \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# ═══════════════════════════════════════════════════════════════
# Stage 2: PHP-FPM runtime
# ═══════════════════════════════════════════════════════════════
FROM php:8.3-fpm-alpine AS runtime

RUN apk add --no-cache \
        bash \
        git \
        curl \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        sqlite-dev \
        mysql-client \
        linux-headers \
    && apk add --no-cache --virtual .build-deps \
        autoconf \
        gcc \
        g++ \
        make \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        zip \
        intl \
        opcache \
        bcmath \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

RUN addgroup -g 1000 -S laravel \
    && adduser -u 1000 -S laravel -G laravel

COPY --from=vendor /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor

COPY --chown=laravel:laravel . .

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

RUN chown -R laravel:laravel storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER laravel

EXPOSE 9000

CMD ["php-fpm"]
