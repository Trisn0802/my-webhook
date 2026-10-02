FROM php:8.2-apache

# Pastikan ekstensi SQLite tersedia (sudah ada di image resmi, pasang ulang bila tidak).
RUN php -m | grep -qi pdo_sqlite || docker-php-ext-install pdo_sqlite

# Aktifkan mod_rewrite untuk .htaccess.
RUN a2enmod rewrite

# Salin aplikasi.
COPY . /var/www/html

# Folder data harus dapat ditulis oleh Apache.
RUN mkdir -p /var/www/html/data \
 && chown -R www-data:www-data /var/www/html/data \
 && chmod -R 775 /var/www/html/data

# Jangan tampilkan direktori bila index tidak ada (bawaan aman).
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' \
      /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf || true

EXPOSE 80
