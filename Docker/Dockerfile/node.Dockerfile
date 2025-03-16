FROM node:20 as build

WORKDIR /var/www

# Copy dependencies and install
# COPY package*.json vite.config.js ./
# RUN npm install

# COPY . .

RUN npm run build

EXPOSE 5173

CMD ["echo", "Frontend build complete!"]