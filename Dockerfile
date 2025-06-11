FROM dropinbase/angular:ng17
# RUN apt-get update --fix-missing

# TODO /vendor/dropinbase/dropinbase should be copied from somewhere else
# COPY test-app.php /dropinbase/index.php
# COPY . /dropinbase/

# Copy configuration files
# COPY virtualhost.conf /etc/apache2/sites-enabled/000-default.conf
# COPY virtualhost-ssl.conf /etc/apache2/sites-enabled/default-ssl.conf
#RUN a2dissite 000-default.conf


WORKDIR /dropinbase

# Downgrade the minimum TLS protocol level to TLS v1.0
# This should be removed as soon as the APIs support higher versions of TLS
RUN sed -i 's/MinProtocol = TLSv1.2/MinProtocol = TLSv1.0/g' /etc/ssl/openssl.cnf

# COPY any pre-generated cache
RUN chown application:application /dropinbase -R
WORKDIR /dropinbase

COPY package.json /dropinbase/

COPY composer.json /dropinbase/
RUN php /usr/local/bin/composer install --prefer-dist 
ENV Dropinbase_Vendor_Path /vendor
#COPY nginx/* /opt/docker/etc/nginx/
#COPY nginx/vhost.common.d/* /opt/docker/etc/nginx/vhost.common.d/
#COPY nginx.conf /etc/nginx/nginx.conf
#COPY php.webdevops.ini /opt/docker/etc/php/php.webdevops.ini

RUN chown application:application /vendor/dropinbase/dropinbase/dropins/setNgxMaterial/angular/projects -R
RUN chown application:application /vendor/dropinbase/dropinbase/dropins/setNgxMaterial/angular/src/ -R
RUN chown application:application /vendor/dropinbase/dropinbase/dropins/setNgxMaterial/angular/dist/ -R
RUN chown application:application /vendor/dropinbase/dropinbase/dropins/setNgxMaterial/dibAdmin/dibCode/ -R

EXPOSE 80
EXPOSE 443
