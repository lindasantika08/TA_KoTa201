#!/bin/bash

# Load environment variables dari .env
set -o allexport
source .env
set +o allexport

php artisan key:generate

# Fungsi untuk menunggu database siap
echo "Menunggu database siap..."
until php -r "try { new PDO('mysql:host=${DB_HOST};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'Database siap.'; } catch (PDOException \$e) { exit(1); }"; do
    sleep 3
    echo "Menunggu database..."
done || { echo "Gagal terhubung ke database."; exit 1; }

# Jalankan migrasi
echo "Menjalankan migrasi..."
if php artisan migrate --force; then
    echo "Migrasi berhasil."
else
    echo "Gagal menjalankan migrasi."
    exit 1
fi

# Jalankan seeder
if [ "${RUN_SEEDER}" = "true" ]; then
    echo "Menjalankan seeder..."
    if php artisan db:seed --force; then
        echo "Seeder berhasil."
    else
        echo "Gagal menjalankan seeder."
        exit 1
    fi
else
    echo "Seeder tidak dijalankan (RUN_SEEDER=false)."
fi

echo "run flask..."
cd TA_201_Flask
python3 app.py

echo "Menjalankan score SLA peer"
cd ..
php artisan queue:work --queue=flask-peer-processing --tries=3 --timeout=300

echo "Menjalankan score SLA self"
php artisan queue:work --queue=flask-processing --tries=3 --timeout=300

# Jalankan server Laravel
echo "Menjalankan server..."
php artisan serve --host=0.0.0.0 --port=8000 && npm run dev

