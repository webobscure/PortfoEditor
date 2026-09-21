# syntax=docker/dockerfile:1

FROM node:24-alpine AS frontend

WORKDIR /src

COPY frontend/package.json frontend/package-lock.json ./frontend/
RUN cd frontend && npm ci

COPY frontend ./frontend
COPY backend/resources/templates ./backend/resources/templates
RUN cd frontend && npm run build


FROM composer:2 AS php-dependencies

WORKDIR /app

COPY backend ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader


FROM php:8.4-apache-bookworm AS runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
        libwebp-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" exif gd opcache pdo_pgsql zip \
    && a2enmod rewrite \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY backend ./
COPY --from=php-dependencies /app/vendor ./vendor
COPY --from=php-dependencies /app/bootstrap/cache ./bootstrap/cache
COPY --from=frontend /src/frontend/dist ./public
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php-production.ini /usr/local/etc/php/conf.d/99-portfoeditor.ini
COPY docker/start.sh /usr/local/bin/portfoeditor-start

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["sh", "/usr/local/bin/portfoeditor-start"]
