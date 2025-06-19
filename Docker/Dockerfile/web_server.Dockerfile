FROM nginx:alpine

WORKDIR /var/www/
COPY public /var/www/public
ADD Docker/nginx/default.conf /etc/nginx/conf.d/default.conf