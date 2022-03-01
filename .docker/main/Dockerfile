FROM drupal:9-apache

ENV PATH="${PATH}:/opt/drall/bin"
ENV PHP_INI_PATH="$PHP_INI_DIR/conf.d/php.ini"

RUN apt-get update && \
	apt-get install -qy mariadb-client git unzip

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_PATH" \
    && pear config-set php_ini "$PHP_INI_PATH" \
    && pecl install --force xdebug