#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
./bin/configure.sh
set -a
# .env генерируется configure.sh; не используйте файлы из непроверенных источников.
source .env
set +a
docker compose up -d --wait --wait-timeout 180
if ./bin/wp core is-installed >/dev/null 2>&1; then
  echo 'WordPress уже установлен. Существующие настройки и содержимое сохранены.'
  exit 0
fi
# Секреты передаются окружением; PHP-установщик не выводит пароль.
docker compose exec -T -e WP_ADMIN_PASSWORD -e WP_ADMIN_USER -e WP_ADMIN_EMAIL -e WP_URL wordpress php /opt/gorizont/install.php
./bin/wp option update blogdescription 'Стратегия. Дизайн. Разработка сайтов.'
./bin/wp option update timezone_string Europe/Moscow
./bin/wp option update blog_public 0
./bin/wp option update default_comment_status closed
./bin/wp rewrite structure '/%postname%/'
./bin/wp plugin activate gorizont-core
./bin/wp theme activate gorizont
./bin/wp gorizont demo --configure
# Локализация загружается отдельно: при отсутствии сети сайт остаётся доступен.
if ./bin/wp language core install ru_RU --activate; then
  ./bin/wp user meta update 1 locale ru_RU
else
  echo 'Не удалось скачать ru_RU. Повторите: ./bin/wp language core install ru_RU --activate' >&2
fi
printf 'Сайт: %s\nПанель: %s/wp-admin/\nПароль: файл .env\n' "$WP_URL" "$WP_URL"
