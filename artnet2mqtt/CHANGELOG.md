# Changelog

All notable changes to the Art-Net2MQTT Home Assistant Add-on will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-02-19

### Fixed
- Replaced `python_artnet` dependency with a pure-Python UDP socket implementation
  to eliminate `Segmentation fault` / `Illegal instruction` crashes caused by
  incompatible native C extensions on ARM (aarch64/armv7) hardware
- Removed `pip3 install --upgrade pip` from Dockerfile — upgrading the Alpine
  system pip via pip itself creates a split installation that could cause packages
  to be installed in a path Python does not load from; dependencies are now
  installed inside an isolated virtual environment (`/opt/venv`)

### Changed
- Base image updated from `ghcr.io/hassio-addons/base:18.1.0` to `20.0.1`
  (Alpine 3.23.3, latest bashio, updated system libraries)
- Python dependencies now installed in a dedicated virtual environment for clean
  isolation from the Alpine system Python

### Removed
- `python_artnet==1.2.0` dependency (replaced by built-in `socket` + `struct`)

## [1.0.0] - 2025-09-01

### Added
- Initial release of Art-Net2MQTT Home Assistant Add-on
- Art-Net DMX512 protocol support on UDP port 6454
- Multi-universe support (universes 0-15)
- Configurable channel monitoring (1-512 channels per universe)
- MQTT integration with Home Assistant Discovery
- Automatic sensor creation for DMX channels
- Real-time DMX data reception and publishing
- Performance optimization features:
  - Configurable throttling (millisecond precision)
  - Change-only publishing to reduce MQTT traffic
  - Adjustable monitoring intervals
- Comprehensive logging system:
  - Seven log levels (trace, debug, info, notice, warning, error, fatal)
  - Configurable verbosity for troubleshooting
- Multi-language support:
  - English interface and documentation
  - Portuguese translation (Português)
  - Spanish translation (Español)
- Network configuration:
  - Custom MQTT broker support
  - Flexible authentication (username/password optional)
  - Configurable discovery prefix
- Device management:
  - Custom device naming
  - Object prefix customization
  - Availability tracking
- Advanced features:
  - Art-Net source IP detection and reporting
  - Packet statistics monitoring
  - Comprehensive error handling and validation
- Docker multi-architecture support:
  - AMD64 (x86_64)
  - ARM64 (aarch64) 
  - ARMv7 (32-bit ARM)
