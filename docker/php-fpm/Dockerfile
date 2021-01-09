FROM php:8.0-fpm-alpine as main

LABEL maintainer="igoragapie@gmail.com"

RUN set -ex \
    && mkdir -p /app \
    && chown -Rf www-data:www-data /app \
    && apk add --no-cache --virtual .build-deps \
    	autoconf \
    	dpkg-dev \
    	dpkg \
    	file \
    	g++ \
    	gcc \
    	libc-dev \
    	make \
    	pkgconf \
    	re2c \
    	libgcrypt-dev \
    	libxslt-dev \
    	libxml2-dev \
    && docker-php-ext-install \
		xsl \
		soap \
		pdo_mysql \
	&& runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )" \
    && apk add --no-cache --virtual .php-rundeps $runDeps \
    && apk del .build-deps

WORKDIR /app

FROM main as dev

ARG USER_ID
ARG GROUP_ID

RUN set -ex \
	&& apk add --no-cache --virtual .persistent-deps \
		nano \
		unzip \
		zip \
		git \
		shadow \
    && groupdel dialout \
    && groupmod -g $GROUP_ID www-data \
	&& usermod -u $USER_ID www-data \
	&& chown -Rf www-data:www-data /app \
	&& mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

RUN set -ex \
	&& apk add --no-cache --virtual .build-deps \
    	autoconf \
    	dpkg-dev \
    	dpkg \
    	file \
    	g++ \
    	gcc \
    	libc-dev \
    	make \
    	pkgconf \
    	re2c \
    	libgcrypt-dev \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && curl -fsSL "https://github.com/xdebug/xdebug/archive/3.0.0.tar.gz" -o xdebug-3.0.0.tar.gz \
    && mkdir -p /usr/src/php/ext \
    && tar -C /usr/src/php/ext -xzf xdebug-3.0.0.tar.gz \
    && rm xdebug-3.0.0.tar.gz \
    && mv /usr/src/php/ext/xdebug-3.0.0 /usr/src/php/ext/xdebug \
    && docker-php-ext-configure xdebug --enable-xdebug-dev \
    && docker-php-ext-install xdebug \
    && mkdir -p /tmp/debug \
    && chown -Rf www-data:www-data /tmp/debug \
    && docker-php-source delete \
	&& runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )" \
    && apk add --no-cache --virtual .php-rundeps $runDeps \
    && apk del .build-deps \
    && echo '#!/bin/sh' >> /usr/local/bin/xdebug \
	&& echo 'set -e' >> /usr/local/bin/xdebug \
	&& echo '' >> /usr/local/bin/xdebug \
	&& echo 'if [ -s "/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini" ]; then' >> /usr/local/bin/xdebug \
	&& echo '    mv /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.iniOLD \' >> /usr/local/bin/xdebug \
	&& echo '    && kill -USR2 1 \' >> /usr/local/bin/xdebug \
	&& echo '    && echo "========= XDebug was disabled ========="' >> /usr/local/bin/xdebug \
	&& echo 'else' >> /usr/local/bin/xdebug \
	&& echo '    mv /usr/local/etc/php/conf.d/docker-php-ext-xdebug.iniOLD /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \' >> /usr/local/bin/xdebug \
	&& echo '    && kill -USR2 1 \' >> /usr/local/bin/xdebug \
	&& echo '    && echo "========= XDebug was enabled ========="' >> /usr/local/bin/xdebug \
	&& echo 'fi' >> /usr/local/bin/xdebug \
	&& chmod +x /usr/local/bin/xdebug

COPY docker-php-* /usr/local/etc/php/conf.d/

EXPOSE 9003
