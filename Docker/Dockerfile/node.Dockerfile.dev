# Set the base image
FROM node:20

# Set working directory
WORKDIR /var/www

# Copy `package.json` and `package-lock.json`
COPY package*.json ./

# Install project dependencies
RUN npm install

# Expose the port Vite runs on
EXPOSE 5173

# Start the Vite server
CMD ["npm", "run", "dev"]