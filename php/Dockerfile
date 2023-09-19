# web/Dockerfile
FROM php:8.0-apache

# �p�b�P�[�W�̍X�V
RUN apt-get update

# PHP�֘A�̒ǉ�
RUN apt-get install -y libonig-dev \
  && docker-php-ext-install pdo_mysql mysqli mbstring
ADD ./php.ini /usr/local/etc/php/php.ini


# �^�C���]�[����`
ENV TZ Asia/Tokyo
RUN echo "${TZ}" > /etc/timezone \
  && dpkg-reconfigure -f noninteractive tzdata

# wget���C���X�g�[������悤�C��
RUN apt-get update && apt-get install -y wget

# �z�X�g���ō����mailhog.ini���R���e�i�ɃR�s�[
COPY mailhog.ini /usr/local/etc/php/conf.d/mailhog.ini

# mhsendmail�R�}���h�̃C���X�g�[���BCURL�Ń_�E�����[�h���ăC���X�g�[�����܂��B
RUN curl -sSL https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64 -o mhsendmail \
  && chmod +x mhsendmail \
  && mv mhsendmail /usr/local/bin/mhsendmail

# .htaccess�̗L����
RUN a2enmod rewrite

RUN pecl install xdebug && \
  docker-php-ext-enable xdebug