FROM php:8.4-fpm-bookworm@sha256:6bfef8e416977aa41f48e3e42a40c1e08050d24e4a938c6edb421400bff24601

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev libonig-dev libsqlite3-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql pdo_sqlite mbstring intl zip bcmath \
    && pecl install pcov-1.0.12 \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
ENV COMPOSER_HOME=/tmp/composer

# Imagem de avaliacao local: inclui as dependencias de testes e cobertura.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader
COPY . .
RUN mkdir -p /tmp/composer bootstrap/cache storage/app/private storage/app/public \
    storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && touch .env \
    && composer dump-autoload --optimize --no-interaction \
    && mkdir -p vendor/nunomaduro/collision/.temp \
    && chown -R www-data:www-data vendor/nunomaduro/collision/.temp \
    && chown -R www-data:www-data /tmp/composer bootstrap/cache storage .env \
    && git config --system --add safe.directory /var/www/html
USER www-data
CMD ["php-fpm"]
