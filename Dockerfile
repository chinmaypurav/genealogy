############################################
# Base Image
############################################

# Learn more about the Server Side Up PHP Docker Images at:
# https://serversideup.net/open-source/docker-php/
FROM serversideup/php:8.4-fpm-nginx AS base

# Switch to root before installing our PHP extensions
USER root

RUN install-php-extensions gd xsl exif intl

USER www-data

############################################
# Development Image
############################################
FROM base AS development

# We can pass USER_ID and GROUP_ID as build arguments
# to ensure the www-data user has the same UID and GID
# as the user running Docker.
ARG USER_ID=1000
ARG GROUP_ID=1000

# Switch to root so we can set the user ID and group ID
USER root
RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID  && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

RUN install-php-extensions pcov

# Switch back to the unprivileged www-data user
USER www-data

############################################
# CI image
############################################
FROM serversideup/php:8.4-cli AS ci

ENV PHP_MEMORY_LIMIT=2G

# Sometimes CI images need to run as root
# so we set the ROOT user and configure
# the PHP-FPM pool to run as www-data
USER root

RUN install-php-extensions intl gd xsl exif pcov

############################################
# Composer stage for installing PHP dependencies
############################################
FROM base AS composer-stage

USER root

# Install composer and git
RUN apt-get update && apt-get install -y git && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies (needed for Filament CSS files)
# Disable SSL verification temporarily due to Docker environment constraints
RUN composer config --global disable-tls true && \
    composer config --global secure-http false && \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist

############################################
# Node stage for compiling assets
############################################
FROM node:20 AS node

WORKDIR /app

# Copy package files
COPY package.json ./

# Install dependencies using yarn 
# Disable strict SSL temporarily due to Docker environment constraints
RUN yarn config set strict-ssl false && \
    yarn install --network-timeout 1000000 && \
    yarn config set strict-ssl true

# Copy source files needed for build
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY tailwind.config.js ./

# Copy vendor directory from composer stage (needed for Filament CSS imports)
COPY --from=composer-stage /app/vendor ./vendor

# Build assets
RUN npm run build

############################################
# Production Image
############################################
FROM base AS deploy

# Laravel Autorun Automations - ServerSideUp configurations
ENV AUTORUN_ENABLED=true
ENV AUTORUN_LARAVEL_MIGRATION=true
ENV AUTORUN_LARAVEL_STORAGE_LINK=true
ENV AUTORUN_LARAVEL_EVENT_CACHE=true
ENV AUTORUN_LARAVEL_ROUTE_CACHE=true
ENV AUTORUN_LARAVEL_VIEW_CACHE=true
ENV AUTORUN_LARAVEL_CONFIG_CACHE=true
ENV PHP_OPCACHE_ENABLE=1

# Copy the rest of the application
COPY --chown=www-data:www-data . /var/www/html
COPY --chown=www-data:www-data --chmod=755 .docker/etc/entrypoint.d /etc/entrypoint.d

# Copy compiled assets from node stage
COPY --from=node --chown=www-data:www-data /app/public/build /var/www/html/public/build

RUN rm -rf tests/

RUN composer install --no-dev --optimize-autoloader --no-scripts
