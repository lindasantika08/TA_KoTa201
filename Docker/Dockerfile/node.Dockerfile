FROM node:20 as build

WORKDIR /var/www

# Copy dependencies and install
COPY package*.json vite.config.js ./

RUN npm install

EXPOSE 5173

CMD ["npm", "run", "dev"]