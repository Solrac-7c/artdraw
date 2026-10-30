FROM php:8.3-apache

# Instala extensões necessárias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilita mod_rewrite e autoindex
RUN a2enmod rewrite autoindex

RUN echo "<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/conf-available/custom.conf \
&& a2enconf custom