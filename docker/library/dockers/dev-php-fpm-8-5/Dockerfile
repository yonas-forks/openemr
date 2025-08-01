# syntax=docker/dockerfile:1
#
# Copyright (C) 2018-2019 Brady Miller <brady.g.miller@gmail.com>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# php-fpm Dockerfile build for openemr development docker environment
# This docker is hosted here: https://hub.docker.com/r/openemr/dev-php-fpm/ <tag is 7.2>
#
FROM openemr/dev-php-fpm:pre-build-dev-85

# Add mariadb-client package that is needed in the OpenEMR Backup gui, which does direct command mysql commands
# Add imagemagick that is needed for some image processing in OpenEMR
# Note this basically add 160MB of space to the docker, so would be nice for OpenEMR to not require this stuff
#  and instead rely on php scripts, if possible.
RUN apt-get update \
 && apt-get install -y curl \
                       imagemagick \
                       mariadb-client

# Install correct version nodejs
RUN curl -sL https://deb.nodesource.com/setup_20.x \
  | bash - \
 && apt-get update \
 && apt-get install -y nodejs

# Temporary fix to get ldap install working by bypassing below install-php-extensions run (these issues are usually temporary
#  in dev versions of PHP, so this is not a permanent solution)
# TODO:
# TODO: intermittently try to remove this block of code and uncomment ldap in below ../install-php-extensions run
# TODO:
RUN apt-get update \
 && apt-get install -y libldap-common \
                       libldap2-dev \
                       libsasl2-dev \
                       pkg-config
RUN LDAP_CFLAGS="-I/usr/include" \
    LDAP_LIBS="-lldap -llber" \
    docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
 && docker-php-ext-install ldap

# Add the php extensions (note using a very cool script by mlocati to do this)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod uga+x /usr/local/bin/install-php-extensions \
 && sync \
 && install-php-extensions calendar \
                           gd \
                           gettext \
                           intl \
                           mysqli \
                           pdo_mysql \
                           soap \
                           sockets \
                           tokenizer \
                           xmlreader \
                           xsl \
                           zip

# Needed to ensure permissions work across shared volumes with openemr, nginx, and php-fpm dockers
RUN usermod -u 1000 www-data

# Copy over the php.ini conf
COPY php.ini /usr/local/etc/php/php.ini
