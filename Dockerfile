# ============================================================
# Stage 1: Node.js — Tailwind CSS v4 + Vite build
# ============================================================
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY package*.json ./
RUN npm install

# Tailwind v4 はビルド時に resources/**/*.blade.php をスキャンして
# 使用クラスを抽出するため、views も含め resources/ 全体をコピーする。
COPY resources      ./resources
COPY vite.config.js ./

RUN npm run build

# ============================================================
# Stage 2: Final — PHP 8.4-FPM + Nginx + Supervisor
# ============================================================
FROM php:8.4-fpm-alpine

# ホスト側ユーザーと UID/GID を揃えるためのビルド引数。
# docker-compose.yml で host の UID/GID を渡す。
ARG UID=1000
ARG GID=1000

# ---------- system packages ----------
RUN apk add --no-cache \
        nginx \
        supervisor \
        sqlite \
        sqlite-dev \
        curl \
        zip \
        unzip \
        git \
        nodejs \
        npm \
        shadow \
        su-exec \
    && docker-php-ext-install pdo pdo_sqlite opcache

# ---------- align www-data UID/GID with host user ----------
# Alpine 既定では www-data は UID/GID=82。バインドマウント越しに
# ホスト側ユーザーと所有権が一致するよう、ビルド時に書き換える。
RUN set -eux; \
    current_gid="$(getent group www-data | cut -d: -f3)"; \
    if [ "${current_gid}" != "${GID}" ]; then \
        existing_group="$(getent group "${GID}" | cut -d: -f1 || true)"; \
        if [ -n "${existing_group}" ] && [ "${existing_group}" != "www-data" ]; then \
            groupdel "${existing_group}"; \
        fi; \
        groupmod -g "${GID}" www-data; \
    fi; \
    current_uid="$(id -u www-data)"; \
    if [ "${current_uid}" != "${UID}" ]; then \
        existing_user="$(getent passwd "${UID}" | cut -d: -f1 || true)"; \
        if [ -n "${existing_user}" ] && [ "${existing_user}" != "www-data" ]; then \
            userdel "${existing_user}"; \
        fi; \
        usermod -u "${UID}" -g "${GID}" www-data; \
    fi

# ---------- Composer ----------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------- PHP ini tuning ----------
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# ---------- application sources ----------
COPY . .

# ---------- built front-end assets ----------
COPY --from=node-builder /app/public/build ./public/build

# ---------- PHP dependencies (production) ----------
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ---------- config files ----------
COPY docker/nginx.conf            /etc/nginx/nginx.conf
COPY docker/supervisord.conf      /etc/supervisor/conf.d/supervisord.conf
COPY docker/supervisord.dev.conf  /etc/supervisor/conf.d/supervisord.dev.conf
COPY docker/entrypoint.sh         /entrypoint.sh
RUN chmod +x /entrypoint.sh

# ---------- permissions ----------
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
