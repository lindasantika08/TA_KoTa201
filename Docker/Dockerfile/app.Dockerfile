FROM php:8.3-fpm as backed

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

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js (latest LTS) from NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Copy project ke dalam container
COPY --chown=www-data:www-data . /var/www/

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chown -R www-data:www-data /var/log/supervisor

# Install dependency
RUN composer install --no-dev --optimize-autoloader
RUN npm install

RUN php artisan config:clear

RUN npm run build

# Expose port
EXPOSE 9002

# Tambahkan konfigurasi supervisor
COPY Docker/supervisor/ /etc/

COPY prod-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/prod-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/prod-entrypoint.sh"]

# Ganti user ke www-data
USER www-data