# zend 2 is provided by the image, though we could switch to composer
FROM jeffersonvv/zend2

# then two dependencies of OX lib
RUN composer require zendframework/zendoauth
RUN composer require zendframework/zendrest
# and just throw it into vendor (so it can load dependencies)
# vendor = /var/www/zend/vendor
COPY OX3_Api_Client2.php vendor/


# if you wanna try debugging with Charles:
#RUN wget www.charlesproxy.com/getssl
#RUN mkdir /usr/local/share/ca-certificates/charles
#RUN mv getssl /usr/local/share/ca-certificates/charles/charles.crt
#RUN chmod 755 /usr/local/share/ca-certificates/charles
#RUN chmod 644 /usr/local/share/ca-certificates/charles/charles.crt
# these seem ignored
#ENV http_proxy=http://host.docker.internal:8888
#ENV https_proxy=https://host.docker.internal:8888