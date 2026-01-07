ARG BUILD_FROM
FROM ${BUILD_FROM}

# Build arguments
ARG BUILD_DATE
ARG BUILD_REF
ARG BUILD_VERSION
ARG BUILD_REPOSITORY

LABEL \
    io.hass.version="${BUILD_VERSION}" \
    io.hass.name="CUPS AirPrint" \
    io.hass.description="CUPS print server with AirPrint support" \
    io.hass.arch="amd64|aarch64" \
    io.hass.type="addon" \
    org.opencontainers.image.title="CUPS AirPrint" \
    org.opencontainers.image.description="Servidor de impressão CUPS com suporte a AirPrint para Home Assistant" \
    org.opencontainers.image.version="${BUILD_VERSION}" \
    org.opencontainers.image.created="${BUILD_DATE}" \
    org.opencontainers.image.revision="${BUILD_REF}" \
    org.opencontainers.image.source="https://github.com/mastria/hassio-addons/tree/main/cups-airprint" \
    org.opencontainers.image.licenses="MIT"

# Set shell
SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# Install CUPS, Avahi (AirPrint), drivers e ferramentas
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        sudo \
        locales \
        whois \
        # CUPS core
        cups \
        cups-filters \
        cups-browsed \
        cups-pdf \
        # Avahi para AirPrint/Bonjour
        avahi-daemon \
        avahi-utils \
        libnss-mdns \
        # D-Bus para comunicação entre serviços
        dbus \
        colord \
        # Drivers de impressora
        printer-driver-all \
        printer-driver-gutenprint \
        openprinting-ppds \
        hpijs-ppds \
        hp-ppd \
        hplip \
        printer-driver-hpcups \
        printer-driver-foo2zjs \
        printer-driver-brlaser \
        printer-driver-c2esp \
        printer-driver-ptouch \
        # Ferramentas úteis
        usbutils \
        nano \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copia rootfs com configurações
COPY rootfs /

# Adiciona usuário print com permissões adequadas
RUN useradd \
        --groups=sudo,lp,lpadmin \
        --create-home \
        --home-dir=/home/print \
        --shell=/bin/bash \
        --password=$(mkpasswd print) \
        print \
    && sed -i '/%sudo[[:space:]]/ s/ALL[[:space:]]*$/NOPASSWD:ALL/' /etc/sudoers

# Configura permissões
RUN chmod a+x /run.sh

# Porta CUPS
EXPOSE 631

CMD ["/run.sh"]
