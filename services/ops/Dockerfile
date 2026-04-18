# ─────────────────────────────────────────────────────────────
#  Equencia Ops Service — Dockerfile multi-stage
# ─────────────────────────────────────────────────────────────

FROM php:8.4-cli-alpine AS base

RUN apk add --no-cache \
    git \
    unzip \
    curl \
    libpq-dev \
    icu-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# ── Développement ────────────────────────────────────────────
FROM base AS dev

ENV APP_ENV=dev
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8003
CMD ["php", "-S", "0.0.0.0:8003", "-t", "public"]

# ── Production ───────────────────────────────────────────────
FROM base AS prod

ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative \
    && php bin/console cache:warmup --env=prod

EXPOSE 8003
CMD ["php", "-S", "0.0.0.0:8003", "-t", "public"]
