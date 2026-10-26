#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "Jalankan dengan sudo dari salinan repo di server."
  exit 1
fi

DOMAIN="${1:-}"
if [[ -z "${DOMAIN}" ]]; then
  echo "Pemakaian: sudo bash deploy/ubuntu/setup.sh nama-domain-anda"
  exit 1
fi

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
APP="${ROOT}/backend"

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y \
  nginx mysql-server \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath \
  unzip git supervisor certbot python3-certbot-nginx composer curl ca-certificates

if ! command -v node >/dev/null 2>&1 || ! node -v | grep -Eq '^v(2[2-9]|[3-9])'; then
  curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
  apt-get install -y nodejs
fi

install -d -o www-data -g www-data "${APP}/storage" "${APP}/bootstrap/cache"
chgrp -R www-data "${APP}/storage" "${APP}/bootstrap/cache"
chmod -R ug+rwx "${APP}/storage" "${APP}/bootstrap/cache"

sed "s/__DOMAIN__/${DOMAIN}/g" "${ROOT}/deploy/ubuntu/nginx.conf" > /etc/nginx/sites-available/ritelin
ln -sfn /etc/nginx/sites-available/ritelin /etc/nginx/sites-enabled/ritelin
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

cp "${ROOT}/deploy/ubuntu/reverb.conf" /etc/supervisor/conf.d/ritelin-reverb.conf
supervisorctl reread
supervisorctl update

echo "Paket Ubuntu siap."
echo "1. Isi ${APP}/.env untuk production, lalu jalankan: bash deploy/ubuntu/deploy.sh"
echo "2. Aktifkan HTTPS: sudo certbot --nginx -d ${DOMAIN}"
