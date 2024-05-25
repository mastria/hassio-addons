#!/usr/bin/with-contenv bashio

INTELBRAS_USER=$(bashio::config 'usuario')
INTELBRAS_PASSWORD=$(bashio::config 'senha')
LOG_LEVEL=$(bashio::config 'log_level')

if [[ -z "$INTELBRAS_USER" ]] || [[ -z "$INTELBRAS_PASSWORD" ]]; then
    echo "Erro: 'usuario' e 'senha' precisam ser informados na configuração."
    exit 1
fi

export INTELBRAS_USER
export INTELBRAS_PASSWORD
export LOG_LEVEL

echo "Running schedules with log level: $LOG_LEVEL..."
sed -i "s/loglevel=debug/loglevel=$LOG_LEVEL/" /home/app/docker-files/supervisord/app.conf
/usr/bin/supervisord -c /home/app/docker-files/supervisord/app.conf
