FROM php:8.1-fpm

# Set PHP ini settings
COPY docker-php.ini /usr/local/etc/php/php.ini

# Install dependencies
RUN apt update \
    && apt install -y openssl zip unzip curl wget ssh vim zlib1g-dev libcurl3-dev libzip-dev libpng-dev libjpeg-dev libwebp-dev libonig-dev  \
    libxml2-dev git rsync mariadb-client libssl-dev libldap2-dev libmemcached-dev

# Install PHP extensions
RUN docker-php-ext-configure ldap --with-libdir=lib/aarch64-linux-gnu/ && \
    docker-php-ext-install curl gd mbstring mysqli pdo pdo_mysql xml soap opcache intl ldap

# Install memcached
RUN pecl install memcached \
    && docker-php-ext-enable memcached


# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Add ssh keys
COPY aladin-config/ssh /root/.ssh

# Set up git config (replace with own info)
RUN git config --global --add safe.directory /app \
    && git config --global user.email "boone@wrlc.org" \
    && git config --global user.name "Tom Boone"

# Add vendor/bin to PATH
ENV PATH="${PATH}:/app/drupal-main/vendor/bin"