#!/bin/sh
set -eu

PROJECT_ROOT="/var/www/project"
HTML_DIR="$PROJECT_ROOT/html"

mkdir -p "$HTML_DIR/thumbnails" "$HTML_DIR/avatars" "$HTML_DIR/images" "$HTML_DIR/tmp"

# The project is bind-mounted from host and may be owned by root:root (0755),
# which prevents Apache's www-data user from writing runtime/cache/upload files.
# Keep writable runtime dirs permissive for local Docker development.
chmod 0777 "$HTML_DIR/tmp" "$HTML_DIR/thumbnails" "$HTML_DIR/avatars" "$HTML_DIR/images"

if [ ! -f "$HTML_DIR/config.ini" ]; then
  cp "$HTML_DIR/config_default.ini" "$HTML_DIR/config.ini"

  sed -i "s|^db_host = .*|db_host = ${DB_HOST:-db}|" "$HTML_DIR/config.ini"
  sed -i "s|^db_user = .*|db_user = ${DB_USER:-newbooru}|" "$HTML_DIR/config.ini"
  sed -i "s|^db_pass = .*|db_pass = ${DB_PASS:-newbooru}|" "$HTML_DIR/config.ini"
  sed -i "s|^db_name = .*|db_name = ${DB_NAME:-newbooru}|" "$HTML_DIR/config.ini"
  sed -i "s|^site_url = .*|site_url = \"${APP_URL:-http://localhost:8080/}\"|" "$HTML_DIR/config.ini"
  sed -i "s|^thumbnail_url = .*|thumbnail_url = \"${APP_URL:-http://localhost:8080/}thumbnails\"|" "$HTML_DIR/config.ini"
  sed -i "s|^debug_level = .*|debug_level = ${BOORU_DEBUG_LEVEL:-5}|" "$HTML_DIR/config.ini"
fi

if [ ! -f "$HTML_DIR/webhooks.ini" ]; then
  cp "$HTML_DIR/webhooks_default.ini" "$HTML_DIR/webhooks.ini"
fi

if [ ! -f "$PROJECT_ROOT/vendor/autoload.php" ]; then
  composer install --no-interaction --prefer-dist
fi

exec "$@"
