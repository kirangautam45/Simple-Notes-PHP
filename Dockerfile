# ============================================
# Stage 1: Builder - Prepare application files
# ============================================
FROM php:8.2-cli-alpine3.20 AS builder

# Install security updates and remove build tools
RUN apk update && apk upgrade --no-cache && rm -rf /var/cache/apk/*

WORKDIR /app

# Copy project
COPY . .

# Remove unnecessary files
RUN rm -rf \
    .git \
    .gitignore \
    .dockerignore \
    Dockerfile \
    docker-compose*.yml \
    *.md \
    tests \
    .env \
    .env.example \
    test_db.php \
    render.yaml \
    .vscode \
    .idea

# ============================================
# Stage 2: Production Runtime
# ============================================
FROM php:8.2-apache

# Install PostgreSQL extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    curl \
    && docker-php-ext-install pdo_pgsql opcache \
    && apt-get remove -y libpq-dev \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Force disable unwanted MPMs by removing their load files
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_worker.load

# Enable Apache modules and ensure only one MPM is active
RUN a2dismod mpm_event; a2dismod mpm_worker; a2enmod mpm_prefork rewrite headers

# Railway requires PORT env variable
ENV PORT=8080

# Configure Apache to use dynamic port
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf

WORKDIR /var/www/html

# Copy application
COPY --from=builder /app /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Use production PHP config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Production PHP optimizations
RUN { \
    echo "expose_php = Off"; \
    echo "display_errors = Off"; \
    echo "log_errors = On"; \
    echo "session.cookie_httponly = 1"; \
    echo "session.use_strict_mode = 1"; \
    echo "opcache.enable=1"; \
    echo "opcache.enable_cli=0"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.max_accelerated_files=10000"; \
} >> "$PHP_INI_DIR/php.ini"

# Expose port
EXPOSE ${PORT}

# Health check
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
CMD curl -f http://localhost:${PORT}/ || exit 1

# Start Apache
CMD ["apache2-foreground"]