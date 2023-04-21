FROM drupal:9-php8.1-apache

ENV PATH="/opt/drall/bin:${PATH}:/opt/drall/vendor/bin"
ENV PHP_INI_PATH="$PHP_INI_DIR/conf.d/php.ini"
ENV DRUPAL_PATH="/opt/drupal"

RUN apt-get update && \
	apt-get install -qy git make mariadb-client unzip vim

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_PATH" \
    && pear config-set php_ini "$PHP_INI_PATH" \
    && pecl install --force xdebug

# Provision Drall.
COPY . /opt/drall

# Provision Drupal.
COPY Makefile /opt/drupal/Makefile
RUN make provision/drupal
