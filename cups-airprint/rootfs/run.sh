#!/usr/bin/with-contenv bashio
set -e

bashio::log.info "Iniciando addon CUPS AirPrint..."

# Configura nível de log
LOG_LEVEL=$(bashio::config 'log_level' 'info')
bashio::log.level "${LOG_LEVEL}"

# Aumenta limite de arquivos abertos
ulimit -n 1048576
bashio::log.debug "Limite de arquivos abertos configurado: $(ulimit -n)"

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

# Inicia D-Bus (necessário para Avahi)
if [ ! -d "/run/dbus" ]; then
    mkdir -p /run/dbus
fi

if [ -f "/var/run/dbus/pid" ]; then
    rm -f /var/run/dbus/pid
fi

bashio::log.info "Iniciando D-Bus daemon..."
dbus-daemon --system --fork

# Inicia Avahi daemon (para AirPrint/Bonjour)
bashio::log.info "Iniciando Avahi daemon para AirPrint..."
avahi-daemon --daemonize --no-chroot

# Aguarda Avahi estar pronto
sleep 2

# Informa sobre acesso à interface web
bashio::log.info "Interface web do CUPS disponível em: http://homeassistant.local:631"
bashio::log.info "Usuário: print | Senha: print"

# Inicia CUPS em foreground
bashio::log.info "Iniciando servidor CUPS..."
exec cupsd -f