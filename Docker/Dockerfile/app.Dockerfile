FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www/

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    libonig-dev \
    libzip-dev \
    jpegoptim optipng pngquant gifsicle \
    ca-certificates \
    vim \
    tmux \
    unzip \
    git \
    cron \
    supervisor \
    curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --with-jpeg=/usr/include/ --with-freetype=/usr/include/
RUN docker-php-ext-install gd
RUN pecl install -o -f redis &&  rm -rf /tmp/pear && docker-php-ext-enable redis
# Install net-tools for netstat
RUN apt-get update && apt-get install -y net-tools && apt-get clean && rm -rf /var/lib/apt/lists/*

# RUN apt-get update && apt-get install -y apache2

# RUN a2enmod rewrite headers

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js (latest LTS) from NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

COPY --chown=www-data:www-data . /var/www/

RUN chown -R www-data:www-data /var/www
RUN chown -R www-data:www-data /var/www/public
RUN chown -R www-data:www-data /var/log/supervisor
RUN chmod -R 755 /var/log/supervisor
# Ensure supervisord.log exists and is owned by www-data
RUN touch /var/www/supervisord.log && chown www-data:www-data /var/www/supervisord.log && chmod 664 /var/www/supervisord.log

# Install dependency
RUN composer install --no-dev --optimize-autoloader
RUN npm install
RUN npm run build

RUN php artisan config:clear

RUN chmod -R 755 storage bootstrap/cache \
 && find storage/app/public -type d -exec chmod 755 {} \; \
 && find storage/app/public -type f -exec chmod 644 {} \; \
 && chmod -R 775 /var/www/public \
 && find storage/app/public -type f -exec chmod 644 {} \;

# Expose port
# EXPOSE 80

# Tambahkan konfigurasi supervisor
COPY Docker/supervisor/ /etc/

# Copy Apache virtual host configuration
# COPY Docker/apache/apache.conf /etc/apache2/sites-available/000-default.conf

COPY prod-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/prod-entrypoint.sh

RUN chown -R www-data:www-data /var/www /var/log/supervisor
EXPOSE 9000
# Override PHP-FPM configuration
# COPY Docker/www/www.conf /usr/local/etc/php-fpm.d/www.conf
# COPY Docker/www//zz-docker.conf /usr/local/etc/php-fpm.d//zz-docker.conf

ENTRYPOINT ["/usr/local/bin/prod-entrypoint.sh"]

# Ganti user ke www-data
USER www-data