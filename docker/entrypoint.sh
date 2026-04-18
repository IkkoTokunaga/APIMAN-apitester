#!/bin/sh
set -e

APP_DIR="/var/www/html"
DB_DIR="$APP_DIR/database"
DB_FILE="$DB_DIR/database.sqlite"

# www-data として実行するためのヘルパー
# （ホスト側 UID と一致しているので生成ファイルもホストから編集可能）
as_www() {
    su-exec www-data:www-data "$@"
}

# ---------- .env ----------
if [ ! -f "$APP_DIR/.env" ]; then
    as_www cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    as_www php "$APP_DIR/artisan" key:generate --force
fi
# 既に root 所有で作成されているケースに備えて毎回チェック
chown www-data:www-data "$APP_DIR/.env" 2>/dev/null || true

# ---------- SQLite ----------
mkdir -p "$DB_DIR"
if [ ! -f "$DB_FILE" ]; then
    touch "$DB_FILE"
fi
chown -R www-data:www-data "$DB_DIR"
chmod 775 "$DB_DIR"
chmod 664 "$DB_FILE"

# ---------- storage permissions (volume mount) ----------
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# ---------- migrate ----------
as_www php "$APP_DIR/artisan" migrate --force

# ---------- storage link ----------
as_www php "$APP_DIR/artisan" storage:link --force 2>/dev/null || true

# ---------- npm install (dev only) ----------
APP_ENV="${APP_ENV:-local}"

if [ "$APP_ENV" = "local" ]; then
    # dev: install npm deps in case node_modules is missing
    if [ ! -d "$APP_DIR/node_modules" ]; then
        cd "$APP_DIR" && as_www npm install
    fi
    SUPERVISOR_CONF="/etc/supervisor/conf.d/supervisord.dev.conf"
else
    # production: cache configs
    as_www php "$APP_DIR/artisan" config:cache
    as_www php "$APP_DIR/artisan" route:cache
    as_www php "$APP_DIR/artisan" view:cache
    SUPERVISOR_CONF="/etc/supervisor/conf.d/supervisord.conf"
fi

exec /usr/bin/supervisord -c "$SUPERVISOR_CONF"
