FROM php:8.2-apache

# تثبيت الإضافات الاعتمادية لـ Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql connection

# تفعيل موديل Apache Rewrite للمسارات
RUN a2enmod rewrite

# تغيير الـ Document Root ليكون مجلد public الخاص بـ Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# نسخ ملفات المشروع
WORKDIR /var/www/html
COPY . .

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# إعطاء الصلاحيات للمجلدات الحساسة
RUN chown -m -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80