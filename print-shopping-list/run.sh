#!/usr/bin/with-contenv bashio

if ! [[ -v PRINTER_IP ]]; then
    export PRINTER_IP=$(bashio::config 'printer_ip')
fi

if ! [[ -v PRINTER_PORT ]]; then
    export PRINTER_PORT=$(bashio::config 'printer_port')
fi

if ! [[ -v LOG_LEVEL ]]; then
    export LOG_LEVEL=$(bashio::config 'log_level')
fi

echo "Running HTTP API..."
/usr/bin/supervisord -c /home/app/docker-files/supervisord/webapi.conf
