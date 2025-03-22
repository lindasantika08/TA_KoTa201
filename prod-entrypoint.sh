#!/bin/sh

export $(grep -v '^#' .env | xargs)

echo "Menunggu database siap..."
until php -r "try { new PDO('mysql:host=${DB_HOST};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'Database siap.'; } catch (PDOException \$e) { exit(1); }"; do
    sleep 3
    echo "Menunggu database..."
done || { echo "Gagal terhubung ke database."; exit 1; }
echo "Database siap."

if [ "$RUN_MIGRATIONS" = "true" ]; then
  php artisan migrate:fresh --seed --force
fi

# Start supervisord

exec /usr/bin/supervisord -c /etc/supervisord.conf