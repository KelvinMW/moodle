# OneBoard — Moodle 5.2 on Railway
# PHP 8.3 + Apache, PostgreSQL + Redis, docroot = public/
FROM php:8.3-apache

ENV DEBIAN_FRONTEND=noninteractive

# System libraries for PHP extensions + tools Moodle uses at runtime
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libicu-dev libzip-dev libxml2-dev libxslt1-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev \
        git unzip ghostscript \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required / recommended by Moodle
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pgsql pdo_pgsql gd intl zip soap exif opcache xsl mbstring \
    && pecl install redis \
    && docker-php-ext-enable redis

# Apache modules Moodle needs (clean URLs, caching headers).
# Force a single MPM (mod_php requires prefork) to avoid "More than one MPM loaded".
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true; \
    a2enmod mpm_prefork rewrite headers expires deflate

# PHP runtime tuning for Moodle
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-moodle.ini

# Application code
WORKDIR /var/www/html
COPY . /var/www/html

# Entrypoint: builds config.php from env, points Apache at public/, installs/upgrades DB
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

# Railway injects $PORT; Apache is reconfigured to it at runtime
EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
