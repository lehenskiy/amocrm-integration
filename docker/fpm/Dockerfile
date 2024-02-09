ARG USER
FROM php:8.2.2-fpm

ARG USER

RUN apt-get update \
    && apt-get install -qq libpq-dev libicu-dev \
    && apt-get clean \
    && docker-php-ext-install pdo pdo_pgsql pgsql intl opcache \
    && apt-get install -y postgresql-client \
    && pecl install apcu xdebug \
    && docker-php-ext-enable apcu xdebug

COPY ./docker/fpm/php-overrides.ini "${PHP_INI_DIR}/conf.d/99-php-overrides.ini"
COPY ./docker/fpm/php-fpm-overrides.conf "${PHP_INI_DIR}/../www/zz-docker.conf"

# set owner of related dirs (including volume dirs as VOLUMEs inherit permissions of the existing dirs) to $USER
RUN mkdir -p /var/www/var /var/www/vendor \
    && chown -R $USER:$USER /var/www
USER $USER:$USER

VOLUME /var/www/vendor /var/www/var