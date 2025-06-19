FROM php:8.3-fpm as builder

WORKDIR /var/www/
COPY . .
RUN apt update && apt install -y nodejs npm
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

FROM nginx:alpine

COPY public /var/www/public
COPY --from=builder /var/www/public/build /var/www/public/build
ADD Docker/nginx/default.conf /etc/nginx/conf.d/default.conf