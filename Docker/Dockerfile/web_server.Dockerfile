FROM php:8.3-fpm as builder

WORKDIR /var/www/
COPY . .
RUN apt update && apt install -y nodejs npm
RUN npm install && npm run build

FROM nginx:alpine

COPY --from=builder /var/www/public /var/www/public
ADD Docker/nginx/default.conf /etc/nginx/conf.d/default.conf