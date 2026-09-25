# =========================================================
# STAGE 1 — Node: build the Materio dashboard assets (Vite)
# =========================================================
FROM node:20-slim AS node-build

WORKDIR /build

COPY package.json package-lock.json* ./
RUN npm install

COPY resources/ ./resources/
COPY vite.config.js ./
COPY tailwind.config.js* ./
COPY postcss.config.js* ./

RUN npm run build

# =========================================================
# STAGE 2 — Composer: PHP dependencies (PHP 8.2 to match the
# runtime and the lock file's platform requirements)
# =========================================================
FROM php:8.2-cli AS vendor-build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libzip-dev zlib1g-dev unzip \
    && docker-php-ext-install \
        pdo_mysql mbstring zip gd exif \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction \
    --no-progress

# =========================================================
# STAGE 3 — Runtime: PHP app + compiled assets
# =========================================================
FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=vendor-build /usr/bin/composer /usr/bin/composer
COPY --from=node-build /build/public/build ./public/build

COPY . .

COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080
CMD ["/start.sh"]