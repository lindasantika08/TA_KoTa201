FROM nginx:alpine

COPY public /var/www/public
COPY --from=builder /var/www/public/build /var/www/public/build
ADD Docker/nginx/default.conf /etc/nginx/conf.d/default.conf