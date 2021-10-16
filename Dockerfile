FROM php:7

# installing composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
 php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
 php composer-setup.php && \
 php -r "unlink('composer-setup.php');" && \ 
 mv composer.phar /usr/local/bin/composer

# git is required for composer, micro is my favorite editor
RUN apt update ; apt install -y git micro

COPY composer-example /app
WORKDIR /app

# then two dependencies of OX lib
#RUN composer require zendframework/zendoauth
#RUN composer require zendframework/zendrest
RUN composer update

# overwrite sources downloaded from github if you're building it from THIS repo anyway
# so that you can include any local changes
COPY src ./vendor/openx/ox3-php-api-client/src

CMD tail -f /dev/null

ENTRYPOINT /bin/bash

# if you wanna try debugging with Charles:
#RUN wget www.charlesproxy.com/getssl
#RUN mkdir /usr/local/share/ca-certificates/charles
#RUN mv getssl /usr/local/share/ca-certificates/charles/charles.crt
#RUN chmod 755 /usr/local/share/ca-certificates/charles
#RUN chmod 644 /usr/local/share/ca-certificates/charles/charles.crt
# these seem ignored
#ENV http_proxy=http://host.docker.internal:8888
#ENV https_proxy=https://host.docker.internal:8888
