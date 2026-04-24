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
PERSISTENT_CUPS="/config/cups"

if [ ! -d "${PERSISTENT_CUPS}" ]; then
    bashio::log.info "Primeira execução: copiando configuração padrão do CUPS para armazenamento persistente..."
    mkdir -p "${PERSISTENT_CUPS}"
    cp -a /etc/cups/. "${PERSISTENT_CUPS}/"
fi

# Substitui /etc/cups por symlink para o diretório persistente.
# Assim o CUPS lê e grava DIRETAMENTE em /config/cups, garantindo
# que impressoras cadastradas sobrevivam a reinicializações.
rm -rf /etc/cups
ln -sf "${PERSISTENT_CUPS}" /etc/cups
bashio::log.info "CUPS apontando para diretório persistente: ${PERSISTENT_CUPS}"

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

# Inicia D-Bus system daemon em background
bashio::log.info "Iniciando D-Bus daemon..."
mkdir -p /var/run/dbus
rm -f /var/run/dbus/pid
dbus-daemon --system --fork

# Aguarda D-Bus estar pronto
sleep 1

# Inicia Avahi daemon em background (necessário para AirPrint/Bonjour)
bashio::log.info "Iniciando Avahi daemon para AirPrint..."
if avahi-daemon --daemonize --no-chroot 2>&1; then
    bashio::log.info "Avahi iniciado com sucesso"
    sleep 2
else
    bashio::log.warning "Avahi falhou ao iniciar. AirPrint pode não funcionar."
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

# Define log level do CUPS
export CUPS_DEBUG_LOG=/dev/stderr
export CUPS_ERROR_LOG=/dev/stderr

# Inicia CUPS em foreground (processo principal)
cupsd -f