#!/usr/bin/with-contenv bashio

bashio::log.info "Iniciando addon CUPS AirPrint..."

# Configura nível de log
LOG_LEVEL=$(bashio::config 'log_level' 'info')
bashio::log.level "${LOG_LEVEL}"

# Prepara diretórios necessários
bashio::log.info "Preparando diretórios..."
mkdir -p /var/run/avahi-daemon
mkdir -p /run/cups

# Prepara diretórios persistentes
if [ ! -d "/config/cups" ]; then
    bashio::log.info "Primeira execução: criando configuração persistente do CUPS"
    mkdir -p /config/cups
    cp -R /etc/cups/* /config/cups/ 2>/dev/null || true
fi

# Vincula configuração persistente
if [ ! -L "/etc/cups" ]; then
    bashio::log.info "Vinculando diretório de configuração do CUPS"
    rm -rf /etc/cups
    ln -s /config/cups /etc/cups
fi

# Garante permissões corretas
bashio::log.info "Configurando permissões de usuário"
usermod -aG lp,lpadmin print 2>/dev/null || true

# Lista dispositivos USB disponíveis
if bashio::debug; then
    bashio::log.debug "Dispositivos USB detectados:"
    if command -v lsusb &> /dev/null; then
        lsusb || true
    fi
fi

# Inicia Avahi daemon em background (necessário para AirPrint/Bonjour)
bashio::log.info "Iniciando Avahi daemon para AirPrint..."
avahi-daemon --daemonize --no-chroot || bashio::log.warning "Avahi falhou ao iniciar"

# Aguarda Avahi estar pronto
sleep 2

# Informa sobre acesso à interface web
bashio::log.info "======================================"
bashio::log.info "Interface web do CUPS disponível!"
bashio::log.info "URL: http://homeassistant.local:631"
bashio::log.info "Usuário: print | Senha: print"
bashio::log.info "======================================"

# Inicia CUPS em foreground (processo principal do container)
bashio::log.info "Iniciando servidor CUPS em foreground..."
exec cupsd -f