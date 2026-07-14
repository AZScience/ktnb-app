#!/usr/bin/env bash
# =============================================================================
# KTNB App — Lệnh chạy sau khi upload lên hosting (SSH)
# Cách dùng: chmod +x post-deploy.sh && ./post-deploy.sh
# =============================================================================
set -euo pipefail

cd "$(dirname "$0")/../.."

if [[ ! -f .env ]]; then
  echo "Thiếu file .env — copy deploy/hosting/env.production thành .env và điền thông tin."
  exit 1
fi

if grep -q 'YOUR_APP_KEY' .env 2>/dev/null; then
  echo "Chạy: php artisan key:generate"
  php artisan key:generate --force
fi

composer install --no-dev --optimize-autoloader --no-interaction
php artisan package:discover --ansi

# Xóa public/hot nếu có (tránh @vite trỏ Vite localhost)
rm -f public/hot

php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo "Deploy xong. Kiểm tra: APP_URL trong .env và Document Root = public/"
