FROM nginx:1.17.10-alpine as builder

COPY ./ /var/www/html

WORKDIR /var/www/html

RUN apk update && apk add php7 php7-fpm php7-xml php7-simplexml php7-tokenizer php7-iconv php7-ctype php7-xmlwriter \
    php7-xmlreader php7-xmlrpc php7-fileinfo php7-mbstring php7-pdo php7-dom php7-gd \
	php7-pdo_pgsql php7-curl php7-zip php7-openssl php7-json php7-phar php7-zlib php7-session vim \
	unzip zip ca-certificates curl git supervisor composer

RUN composer install && composer dumpautoload

RUN cp .env.example .env

RUN chmod -R 777 /var/www/html/storage

COPY ./docker/nginx.conf /etc/nginx/conf.d/default.conf

COPY ./docker/php.ini /etc/php7/php.ini

COPY ./docker/supervisord.conf /root/supervisord.conf

CMD supervisord -n -c /root/supervisord.conf
