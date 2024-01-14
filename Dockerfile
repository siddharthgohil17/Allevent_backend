# Use the official PHP image
FROM php:latest

# Set the working directory
WORKDIR /var/www

# Install PDO MySQL extension
RUN docker-php-ext-install pdo_mysql

# Copy the application files to the container
COPY . /var/www

# Expose the port your application runs on
EXPOSE 8080

# Start the PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
