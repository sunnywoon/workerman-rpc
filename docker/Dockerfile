FROM php:7.4-cli-alpine3.13
# Set working dir.
ARG CONTAINER_APP_DIR

ARG timezone

ENV TIMEZONE=${timezone:-"Asia/Shanghai"} \
    APP_ENV=prod \
    SCAN_CACHEABLE=(true)


# Change alpinelinux source.
# Reference: https://github.com/laradock/laradock
ARG CHANGE_AlPINE_SOURCE
ARG CONTAINER_PACKAGE_URL
RUN if [ $CHANGE_AlPINE_SOURCE ] ; then sed -i "s/dl-cdn.alpinelinux.org/${CONTAINER_PACKAGE_URL}/g" /etc/apk/repositories ; fi

RUN uname -a && \
    apk update

RUN docker-php-ext-install sockets pcntl && \
    docker-php-ext-install pdo_mysql

RUN apk add --no-cache pcre-dev $PHPIZE_DEPS && \
    pecl install redis && \
    docker-php-ext-enable redis

ARG INSTALL_LIB_EVENT
RUN if [ ${INSTALL_LIB_EVENT} = true ]; then \
    apk add libevent-dev openssl-dev libressl-dev && \
    pecl install event && \
    echo extension=event.so > /usr/local/etc/php/conf.d/event.ini \
;fi

# update
RUN set -ex \
    # show php version and extensions
    && php -v \
    && php -m \
    #  ---------- some config ----------
    && cd /usr/local/etc/php \
    # - config PHP
    && { \
        echo "upload_max_filesize=128M"; \
        echo "post_max_size=128M"; \
        echo "memory_limit=1G"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/99_overrides.ini \
    # - config timezone
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    # ---------- clear works ----------
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"

# Install composer and set mirror.
# Reference: https://pkg.phpcomposer.com/#how-to-install-composer
# Referencr: https://developer.aliyun.com/composer
ARG INSTALL_COMPOSER
ARG COMPOSER_INSTALLER
ARG COMPOSER_MIRROR
RUN if [ ${INSTALL_COMPOSER} = true ]; then \
    php -r "copy('${COMPOSER_INSTALLER}', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer && \
    composer config -g repo.packagist composer ${COMPOSER_MIRROR} \
;fi


RUN apk add git zip vim bash

# Expose port
ARG CONTAINER_PORT
EXPOSE ${CONTAINER_PORT}

WORKDIR ${CONTAINER_APP_DIR}
