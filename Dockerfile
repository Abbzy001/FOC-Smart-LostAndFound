FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mbstring pdo_mysql \
    && rm -rf /var/lib/apt/lists/* \
    && a2dismod mpm_event mpm_worker \
    && a2enmod mpm_prefork rewrite \
    && test "$(apache2ctl -M 2>&1 | grep -c 'mpm_.*_module')" -eq 1

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["sh", "-c", "a2dismod mpm_event mpm_worker && a2enmod mpm_prefork rewrite && exec apache2-foreground"]
