# =========================================================
# STAGE 1 — Node: build the Materio dashboard assets (Vite)
# =========================================================
FROM node:20-slim AS node-build

WORKDIR /build

COPY package.json package-lock.json* ./
RUN npm install

COPY resources/ ./resources/
COPY vite.config.js ./
COPY vite.icons.plugin.js ./
COPY tailwind.config.js* ./
COPY postcss.config.js* ./

RUN npm run build

# =========================================================
# STAGE 2 — Composer: PHP dependencies
# =========================================================
FROM php:8.2-cli AS vendor-build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql mbstring zip gd exif

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

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql mbstring zip gd exif opcache

WORKDIR /app

COPY --from=vendor-build /usr/bin/composer /usr/bin/composer
COPY --from=vendor-build /app/vendor ./vendor
COPY --from=node-build /build/public/build ./public/build

COPY . .

COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080
CMD ["/start.sh"]