# syntax=docker/dockerfile:1

###############################################################################
# 1) PHP base (runtime + extensiones)
###############################################################################
FROM php:8.4-fpm-alpine AS php-base

RUN apk add --no-cache \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libpq \
        libzip \
        freetype \
        nginx \
        supervisor \
        su-exec \
        tzdata \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        zip \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh

###############################################################################
# 2) Dependencias de PHP (sin dev)
###############################################################################
FROM php-base AS vendor

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --no-autoloader

###############################################################################
# 3) Assets (Vite+ / Tailwind). Necesita el CSS de Flux desde vendor/.
###############################################################################
FROM node:22-bookworm-slim AS assets

WORKDIR /app

RUN corepack enable && corepack prepare pnpm@10 --activate

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
RUN pnpm install --frozen-lockfile

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

COPY --from=vendor /var/www/html/vendor/livewire/flux ./vendor/livewire/flux
COPY --from=vendor /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN pnpm run build

###############################################################################
# 4) Imagen final
###############################################################################
FROM php-base AS runtime

WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=10000

COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && composer dump-autoload --no-dev --optimize --no-interaction

EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]
