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
else
    bashio::log.info "Restaurando configuração persistente do CUPS"
    # Copia config persistente para o local esperado pelo CUPS
    cp -R /config/cups/* /etc/cups/ 2>/dev/null || true
fi

# Garante que o diretório de spool existe
mkdir -p /var/spool/cups
mkdir -p /var/cache/cups

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
# Nota: Com host_dbus=true, o Avahi pode falhar mas não é crítico
bashio::log.info "Iniciando Avahi daemon para AirPrint..."
if avahi-daemon --daemonize --no-chroot 2>&1; then
    bashio::log.info "Avahi iniciado com sucesso"
    sleep 2
else
    bashio::log.warning "Avahi não iniciou (normal com host_dbus). AirPrint pode não funcionar."
fi

# Informa sobre acesso à interface web
bashio::log.info "======================================"
bashio::log.info "Interface web do CUPS disponível!"
bashio::log.info "URL: http://homeassistant.local:631"
bashio::log.info "Usuário: print | Senha: print"
bashio::log.info "======================================"

# Inicia CUPS em foreground (processo principal do container)
bashio::log.info "Iniciando servidor CUPS em foreground..."

# Testa se cupsd está disponível
if ! command -v cupsd &> /dev/null; then
    bashio::log.error "cupsd não encontrado!"
    exit 1
fi

# Testa configuração do CUPS
bashio::log.info "Verificando configuração do CUPS..."
if ! cupsd -t; then
    bashio::log.error "Erro na configuração do CUPS!"
    exit 1
fi

bashio::log.info "CUPS iniciando..."
exec cupsd -f