# Single-service image: Laravel serves the JSON API under /api/* and the built
# React SPA (static files in public/). The build context is the repo root so the
# image can see both /web and /api.

# Stage 1: build the React app.
FROM node:22-alpine AS web
WORKDIR /web
COPY web/package*.json ./
RUN npm ci
COPY web/ ./
# This stage has no sibling /api, so override the config's outDir (../api/public)
# to a local dist/, which the PHP stage copies into public/.
RUN npx vite build --outDir dist --emptyOutDir

# Stage 2: PHP runtime and the Laravel app.
FROM php:8.4-cli-alpine AS app

# Laravel 13 needs Mbstring; the official image bundles the rest (ctype, dom,
# tokenizer, xml, and so on). On Alpine these need dev headers to compile:
# mbstring needs oniguruma-dev, pdo_sqlite needs sqlite-dev, zip needs libzip-dev.
RUN apk add --no-cache git unzip oniguruma-dev libzip-dev sqlite-dev \
 && docker-php-ext-install pdo_sqlite mbstring zip bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

# Copy the Laravel source, then install prod dependencies in one step.
COPY api/ ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Merge the freshly built SPA into public/ without clobbering Laravel's own
# public/index.php and .htaccess (COPY merges, it doesn't delete).
COPY --from=web /web/dist/ ./public/

# Deterministic DB config. There is no .env in the image, so don't lean on
# framework defaults for the build-time migrate/seed or the runtime config:cache.
ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/app/database/database.sqlite

# Bake the seeded SQLite database into the image so every boot has the known demo
# accounts and offers. migrate/seed don't touch the encrypter, so no APP_KEY is
# needed at build; the real key is injected at runtime by Render. SQLite needs the
# file and its directory writable for its -wal and -journal sidecars.
RUN mkdir -p database \
 && touch database/database.sqlite \
 && php artisan migrate --force --seed \
 && chmod -R 775 storage bootstrap/cache \
 && chmod 664 database/database.sqlite \
 && chmod 775 database

# Routes don't depend on env, so cache them now. This works only because the SPA
# fallback is an invokable controller rather than a closure. Config is cached at
# runtime instead, once Render's env vars (APP_KEY, APP_URL) are present.
RUN php artisan route:cache

EXPOSE 8080
CMD ["sh", "-c", "php artisan config:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
