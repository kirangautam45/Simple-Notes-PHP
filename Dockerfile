# Production Dockerfile for PHP Notes App
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache to listen on PORT env var (required by Render)
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create data directory for SQLite database and set permissions
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/data

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set recommended PHP settings
RUN echo "session.cookie_httponly = 1" >> "$PHP_INI_DIR/php.ini" \
    && echo "session.cookie_secure = 1" >> "$PHP_INI_DIR/php.ini" \
    && echo "session.use_strict_mode = 1" >> "$PHP_INI_DIR/php.ini" \
    && echo "expose_php = Off" >> "$PHP_INI_DIR/php.ini"

# Set environment variable for port (Render provides this)
ENV PORT=10000

# Expose the port
EXPOSE ${PORT}

# Start Apache in foreground
CMD ["apache2-foreground"]
