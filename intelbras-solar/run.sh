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

php artisan intelbras:verificar-geracao

echo "Running schedules..."
/usr/bin/supervisord -c /home/app/docker-files/supervisord/app.conf
