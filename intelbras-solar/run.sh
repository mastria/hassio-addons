#!/usr/bin/with-contenv bashio

INTELBRAS_USER=$(bashio::config 'usuario')
INTELBRAS_PASSWORD=$(bashio::config 'senha')
LOG_LEVEL=$(bashio::config 'log_level')
TELEGRAM_BOT_TOKEN=$(bashio::config 'telegram_bot_token')
TELEGRAM_CHAT_IDS=$(bashio::config 'telegram_chat_ids')
SCHEDULE_INTERVAL=$(bashio::config 'schedule_interval')

if [[ -z "$INTELBRAS_USER" ]] || [[ -z "$INTELBRAS_PASSWORD" ]]; then
    echo "Erro: 'usuario' e 'senha' precisam ser informados na configuração."
    exit 1
fi

export INTELBRAS_USER
export INTELBRAS_PASSWORD
export LOG_LEVEL
export TELEGRAM_BOT_TOKEN
export TELEGRAM_CHAT_IDS
export SCHEDULE_INTERVAL

case "$LOG_LEVEL" in
    emergency|alert|critical|error)
        export SUPERVISOR_LOG_LEVEL="error"
        export SCHEDULE_VERBOSITY="--quiet"
        ;;
    warning)
        export SUPERVISOR_LOG_LEVEL="warn"
        export SCHEDULE_VERBOSITY="--quiet"
        ;;
    notice|info)
        export SUPERVISOR_LOG_LEVEL="info"
        export SCHEDULE_VERBOSITY=""
        ;;
    debug)
        export SUPERVISOR_LOG_LEVEL="debug"
        export SCHEDULE_VERBOSITY="-vvv"
        ;;
    *)
        export SUPERVISOR_LOG_LEVEL="info"
        export SCHEDULE_VERBOSITY=""
        ;;
esac

php artisan intelbras:verificar-geracao

echo "Running schedules..."
/usr/bin/supervisord -c /home/app/docker-files/supervisord/app.conf
