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
    if php artisan db:wipe; then
        echo "Membersihkan database berhasil."
    else
        echo "Gagal Membersihkan database."
        exit 1
    fi
fi

# Jalankan seeder
if [ "${RUN_SEEDER}" = "true" ]; then
    echo "Menjalankan seeder..."
    if php artisan db:seed --force; then
        echo "Seeder berhasil."
    else
        echo "Gagal menjalankan seeder."
        if php artisan migrate:refresh --seed; then
            echo "Melakukan migrate refresh berhasil."
        else
            echo "Gagal Melakukan migrate refresh."
            exit 1
        fi
    fi
else
    echo "Seeder tidak dijalankan (RUN_SEEDER=false)."
fi

# echo "Menjalankan queue work SLA peer..."
# if php artisan queue:work --queue=flask-peer-processing --tries=3 --timeout=300; then
#         echo "Menjalankan queue work SLA peer berhasil."
#     else
#         echo "Menjalankan queue work SLA peer gagal."
# fi

# echo "Menjalankan queue work SLA self..."
# if php artisan queue:work --queue=flask-processing --tries=3 --timeout=300; then
#         echo "Menjalankan queue work SLA self berhasil."
#     else
#         echo "Menjalankan queue work SLA self gagal."
# fi

# Jalankan server Laravel
echo "Menjalankan server..."
php artisan serve --host=0.0.0.0 --port=8000
