FROM drupal:11-php8.2-apache

ENV PHP_INI_PATH="$PHP_INI_DIR/conf.d/php.ini"
ENV DRUPAL_PATH="/opt/drupal"

RUN apt-get update && \
	apt-get install -qy git make mariadb-client unzip vim rsync

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_PATH" \
    && pear config-set php_ini "$PHP_INI_PATH" \
    && pecl install --force xdebug

RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install pcntl

COPY . /opt/drall
COPY Makefile /opt/drupal/Makefile
COPY Makefile /opt/no-drupal/Makefile
COPY Makefile /opt/empty-drupal/Makefile

RUN echo ". /opt/drall/.docker/main/.profile" >> /root/.profile
RUN echo ". /opt/drall/.docker/main/.profile" >> /root/.bashrc
