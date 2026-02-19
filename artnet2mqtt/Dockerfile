ARG BUILD_FROM
FROM $BUILD_FROM

# Set shell
SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# Copy requirements first for better caching
COPY requirements.txt /tmp/requirements.txt

# Install Python and required packages inside an isolated virtual environment.
# - Never upgrade the Alpine system pip (py3-pip) via pip itself: it creates
#   a split installation (/usr/lib vs /usr/local/lib) that breaks PATH lookups.
# - A venv gives clean, reproducible isolation independent of the system Python.
RUN \
    apk add --no-cache \
        python3 \
        py3-pip \
    && python3 -m venv /opt/venv \
    && /opt/venv/bin/pip install --no-cache-dir -r /tmp/requirements.txt \
    && rm -f /tmp/requirements.txt

# Ensure all subsequent python3/pip3 calls use the venv.
ENV PATH="/opt/venv/bin:${PATH}"

# Copy app files
COPY app/ /app/
COPY rootfs/ /

# Set permissions
RUN chmod a+x /etc/services.d/artnet2mqtt/run \
    && chmod a+x /etc/services.d/artnet2mqtt/finish

# Build arguments
ARG BUILD_ARCH
ARG BUILD_DATE
ARG BUILD_DESCRIPTION
ARG BUILD_NAME
ARG BUILD_REF
ARG BUILD_REPOSITORY
ARG BUILD_VERSION

# Labels
LABEL \
    io.hass.name="${BUILD_NAME}" \
    io.hass.description="${BUILD_DESCRIPTION}" \
    io.hass.arch="${BUILD_ARCH}" \
    io.hass.type="addon" \
    io.hass.version=${BUILD_VERSION} \
    maintainer="Guilherme Mastria <@mastria>" \
    org.opencontainers.image.title="${BUILD_NAME}" \
    org.opencontainers.image.description="${BUILD_DESCRIPTION}" \
    org.opencontainers.image.vendor="Home Assistant Community Add-ons" \
    org.opencontainers.image.authors="Guilherme Mastria <@mastria>" \
    org.opencontainers.image.licenses="MIT" \
    org.opencontainers.image.created=${BUILD_DATE} \
    org.opencontainers.image.revision=${BUILD_REF} \
    org.opencontainers.image.version=${BUILD_VERSION}