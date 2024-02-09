ARG USER
FROM php:8.2.2-cli

ARG USER

# set ENVs for subsequent instructions
ENV COMPOSER_HOME=/home/$USER/.composer

RUN apt-get update \
    && apt-get install -qq p7zip-full libzip-dev zip git curl libfcgi-bin libpq-dev libicu-dev libpng-dev libjpeg-dev \
    && apt-get clean \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip intl gd \
    && curl -sS "https://getcomposer.org/installer" | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -sS https://get.symfony.com/cli/installer | bash \
    && apt-get install -y postgresql-client \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY ./docker/cli/php-overrides.ini "${PHP_INI_DIR}/conf.d/99-php-overrides.ini"

RUN touch /home/$USER/.bash_history \
    && chown -R $USER:$USER /home/$USER /var/www
USER $USER:$USER

ENV HISTFILE=/home/$USER/.bash_history

VOLUME /home/$USER /var/www/var