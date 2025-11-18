FROM nginx:alpine

# Remover configuração padrão
RUN rm /etc/nginx/conf.d/default.conf

# Copiar configuração customizada
COPY docker/nginx/default.conf /etc/nginx/conf.d/

# Criar diretórios para logs
RUN mkdir -p /var/log/nginx

# Expor portas
EXPOSE 80 443

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://localhost/up || exit 1

CMD ["nginx", "-g", "daemon off;"]

