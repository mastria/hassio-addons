#!/usr/bin/with-contenv bashio

ulimit -n 1048576

# Aguarda o avahi-daemon iniciar (requisito do AirPrint e descoberta mDNS)
until [ -e /var/run/avahi-daemon/socket ]; do
  sleep 1s
done

bashio::log.info "Preparando diretórios do CUPS"
# Copia configuração padrão para diretório persistente
cp -v -R /etc/cups /data
rm -v -fR /etc/cups
ln -v -s /data/cups /etc/cups

# Garante que o usuário 'print' tenha acesso aos grupos de impressão
usermod -aG lp,lpadmin print

# Garante que o avahi-daemon esteja rodando (em alguns ambientes ele pode não iniciar automaticamente)
if ! pgrep avahi-daemon > /dev/null; then
    bashio::log.info "Iniciando avahi-daemon"
    avahi-daemon --daemonize
fi

bashio::log.info "Iniciando o servidor CUPS"
cupsd -f