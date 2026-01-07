#!/usr/bin/with-contenv bashio

bashio::log.info "Iniciando addon CUPS AirPrint..."

# Configura nível de log
LOG_LEVEL=$(bashio::config 'log_level' 'info')
bashio::log.level "${LOG_LEVEL}"

# Prepara diretórios persistentes
if [ ! -d "/config/cups" ]; then
    bashio::log.info "Primeira execução: criando configuração persistente do CUPS"
    mkdir -p /config/cups
    cp -R /etc/cups/* /config/cups/ 2>/dev/null || true
fi

# Vincula configuração persistente
if [ ! -L "/etc/cups" ]; then
    bashio::log.debug "Vinculando diretório de configuração do CUPS"
    rm -rf /etc/cups
    ln -s /config/cups /etc/cups
fi

# Garante permissões corretas
bashio::log.debug "Configurando permissões de usuário"
usermod -aG lp,lpadmin print 2>/dev/null || true

# Lista dispositivos USB disponíveis
if bashio::debug; then
    bashio::log.debug "Dispositivos USB detectados:"
    if command -v lsusb &> /dev/null; then
        lsusb || true
    fi
fi

# Inicia Avahi daemon (para AirPrint/Bonjour)
bashio::log.info "Iniciando Avahi daemon para AirPrint..."
avahi-daemon --daemonize --no-chroot

# Aguarda Avahi estar pronto
sleep 2

# Informa sobre acesso à interface web
bashio::log.info "Interface web do CUPS disponível em: http://homeassistant.local:631"
bashio::log.info "Usuário: print | Senha: print"

bashio::log.info "Inicialização completa. Serviços gerenciados pelo s6-overlay."