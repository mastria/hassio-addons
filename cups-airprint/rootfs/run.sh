#!/usr/bin/with-contenv bashio

ulimit -n 1048576

bashio::log.info "Preparando diretórios do CUPS"
# Copia configuração padrão para diretório persistente
cp -v -R /etc/cups /data
rm -v -fR /etc/cups
ln -v -s /data/cups /etc/cups

# Garante que o usuário 'print' tenha acesso aos grupos de impressão
usermod -aG lp,lpadmin print

bashio::log.info "Iniciando o servidor CUPS"
cupsd -f