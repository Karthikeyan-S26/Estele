FROM php:8.4-fpm

# nginx + supervisor (process manager for running nginx + php-fpm in one
# container) + gettext-base (envsubst, used to inject Railway's $PORT into
# the nginx config at container start) alongside the existing extensions.
RUN apt-get update && apt-get install -y \
        git unzip nginx supervisor gettext-base \
        libicu-dev libzip-dev libexif-dev libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && docker-php-ext-install -j"$(nproc)" intl zip exif gd pdo_mysql bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chmod -R 775 storage bootstrap/cache

# php-fpm pool tuning (concurrency — see docker/www.conf for why these numbers)
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# nginx: template (rendered with the real $PORT at container start by
# start.sh) + drop the default site so only ours is active.
RUN mkdir -p /etc/nginx/templates
COPY docker/nginx.conf.template /etc/nginx/templates/nginx.conf.template
RUN rm -f /etc/nginx/sites-enabled/default

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
