FROM nginx:1.18

# ssl certificates
COPY ./certificate/localhost.crt /etc/ssl/certs/
COPY ./certificate/localhost.key /etc/ssl/private/

# server configuration
COPY ./conf.d/api-gateway.conf /etc/nginx/conf.d/api-gateway.conf
COPY ./conf.d/file-manager.conf /etc/nginx/conf.d/file-manager.conf

WORKDIR /www
EXPOSE 443