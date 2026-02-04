FROM nginx:alpine

COPY .ops/docker/nginx/config/99-default-server.conf.tmpl /etc/nginx/conf.d/99-default-server.conf

RUN rm /etc/nginx/conf.d/default.conf