#!/usr/bin/with-contenv bashio

if ! [[ -v INTELBRAS_USER ]]; then
    export INTELBRAS_USER=$(bashio::config 'usuario')
fi

if ! [[ -v INTELBRAS_PASSWORD ]]; then
    export INTELBRAS_PASSWORD=$(bashio::config 'senha')
fi

if ! [[ -v LOG_LEVEL ]]; then
    export LOG_LEVEL=$(bashio::config 'log_level')
fi

echo "Running HTTP API..."
/usr/bin/supervisord -c /home/app/docker-files/supervisord/webapi.conf
