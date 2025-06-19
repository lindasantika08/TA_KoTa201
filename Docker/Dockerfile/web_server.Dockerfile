FROM nginx:alpine

COPY --from=builder /var/www/public /var/www/public
ADD Docker/nginx/default.conf /etc/nginx/conf.d/default.conf
WORKDIR /var/www/