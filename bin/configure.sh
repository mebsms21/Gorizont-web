#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ -e .env ]]; then
  echo '.env уже существует и сохранён.'
  exit 0
fi
command -v openssl >/dev/null || { echo 'Установите openssl.' >&2; exit 1; }
umask 077
{
  echo 'WP_PORT=8080'
  echo 'WP_URL=http://localhost:8080'
  echo 'WP_ADMIN_USER=gorizont_admin'
  echo 'WP_ADMIN_EMAIL=admin@example.com'
  printf 'WP_ADMIN_PASSWORD=%s\n' "$(openssl rand -hex 18)"
  printf 'DB_PASSWORD=%s\n' "$(openssl rand -hex 24)"
  printf 'DB_ROOT_PASSWORD=%s\n' "$(openssl rand -hex 24)"
} > .env
echo 'Создан .env с уникальными локальными паролями. Пароль администратора хранится только в этом файле.'
