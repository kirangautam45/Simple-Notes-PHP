# ============================================
# Stage 1: Base - Shared PHP extensions
# ============================================
FROM php:8.2-apache AS base

# Install PostgreSQL extensions + tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    curl \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql opcache \
    && apt-get remove -y libpq-dev \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Verify extensions loaded correctly (fails build if missing)
RUN php -m | grep -E "pdo_pgsql|pdo" || (echo "ERROR: PDO/pdo_pgsql extension not loaded!" && exit 1)

# Force disable unwanted MPMs
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_worker.load

# Enable Apache modules
RUN a2dismod mpm_event; \
    a2dismod mpm_worker; \
    a2enmod mpm_prefork rewrite headers

WORKDIR /var/www/html

# ============================================
# Stage 2: Builder - Clean up source files
# ============================================
FROM php:8.2-cli-alpine3.20 AS builder

RUN apk update && apk upgrade --no-cache && rm -rf /var/cache/apk/*

WORKDIR /app

COPY . .

# Remove files not needed in production image
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
# Stage 3: Development
# ============================================
FROM base AS development

ENV APP_ENV=development
ENV PORT=8080

# Install Xdebug for debugging
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Development PHP config
RUN { \
    echo "expose_php = On"; \
    echo "display_errors = On"; \
    echo "display_startup_errors = On"; \
    echo "error_reporting = E_ALL"; \
    echo "log_errors = On"; \
    echo "xdebug.mode=debug,coverage"; \
    echo "xdebug.start_with_request=yes"; \
    echo "xdebug.client_host=host.docker.internal"; \
    echo "xdebug.client_port=9003"; \
    echo "opcache.enable=0"; \
    echo "opcache.enable_cli=0"; \
    echo "session.cookie_httponly = 1"; \
    echo "session.use_strict_mode = 1"; \
} > "$PHP_INI_DIR/conf.d/dev.ini"

# Configure Apache port
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf

# Copy app (in dev we typically mount a volume, but copy as fallback)
COPY --from=builder /app /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE ${PORT}

HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD curl -f http://localhost:${PORT}/ || exit 1

CMD ["apache2-foreground"]

# ============================================
# Stage 4: Production
# ============================================
FROM base AS production

ENV APP_ENV=production
ENV PORT=8080

# Use production PHP ini as base
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Production PHP config (no extension= lines — docker-php-ext-install handles that)
RUN { \
    echo "expose_php = Off"; \
    echo "display_errors = Off"; \
    echo "display_startup_errors = Off"; \
    echo "log_errors = On"; \
    echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT"; \
    echo "session.cookie_httponly = 1"; \
    echo "session.use_strict_mode = 1"; \
    echo "session.cookie_secure = 1"; \
    echo "opcache.enable=1"; \
    echo "opcache.enable_cli=0"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.max_accelerated_files=10000"; \
    echo "opcache.revalidate_freq=60"; \
    echo "opcache.validate_timestamps=0"; \
} >> "$PHP_INI_DIR/php.ini"

# Configure Apache port
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf

# Copy cleaned application from builder
COPY --from=builder /app /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE ${PORT}

HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
    CMD curl -f http://localhost:${PORT}/ || exit 1

CMD ["apache2-foreground"]