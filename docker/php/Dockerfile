FROM php:8.0

RUN apt-get update -y && \
	apt-get install -y unzip wget

RUN wget https://getcomposer.org/download/1.10.16/composer.phar -O /usr/local/bin/composer -q && \
	chmod +x /usr/local/bin/composer

RUN pecl install xdebug \
	&& docker-php-ext-enable xdebug opcache

COPY ./xdebug.ini /usr/local/etc/php/conf

WORKDIR /opt/application

CMD ["tail", "-f", "/dev/null"]
