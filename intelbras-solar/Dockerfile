ARG BUILD_FROM
FROM $BUILD_FROM

RUN echo "America/Sao_Paulo" > /etc/timezone && \
    cp /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime

RUN apk add --no-cache \
    supervisor \
    php82 \
    php82-common \
    php82-fpm \
    php82-pdo \
    php82-zip \
    php82-phar \
    php82-iconv \
    php82-cli \
    php82-curl \
    php82-openssl \
    php82-mbstring \
    php82-tokenizer \
    php82-fileinfo \
    php82-json \
    php82-xml \
    php82-xmlwriter \
    php82-simplexml \
    php82-dom \
    php82-pecl-redis \
    php82-pcntl \
    php82-posix \
    php82-session

RUN ln -sf /usr/bin/php82 /usr/bin/php

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir -p /home/app
COPY ./ /home/app
WORKDIR /home/app

RUN php -m

RUN composer install --no-interaction --no-dev

RUN chmod a+x run.sh

CMD [ "/home/app/run.sh" ]
