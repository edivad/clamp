# Official PHP image with Apache already configured to serve /var/www/html.
FROM php:8.5-apache

# The base image does not include the MySQL drivers: add them, otherwise
# PDO cannot open a "mysql:" connection to the MariaDB container.
RUN docker-php-ext-install mysqli pdo_mysql
