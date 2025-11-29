FROM php:8.4-cli
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && install-php-extensions gettext intl zip soap
COPY src /usr/src/abraflexi-tools/src
RUN sed -i -e 's/..\/.env//' /usr/src/abraflexi-tools/src/*.php
COPY composer.json /usr/src/abraflexi-tools
WORKDIR /usr/src/abraflexi-tools
RUN curl -s https://getcomposer.org/installer | php
RUN ./composer.phar install
WORKDIR /usr/src/abraflexi-tools/src
CMD [ "php", "./benchmark.php" ]
