FROM ubuntu:17.10

RUN apt-get update \
  && apt-get install apache2 php bash-completion \
   php7.1-xml php7.1-mysql iputils-ping curl php7.1-curl -y

COPY ./config/veyring_frontend.conf /etc/apache2/sites-available

RUN a2ensite veyring_frontend && a2dissite 000-default && a2enmod rewrite

EXPOSE 80
CMD /usr/sbin/apache2ctl -D FOREGROUND