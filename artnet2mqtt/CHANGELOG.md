# Changelog

All notable changes to the Art-Net2MQTT Home Assistant Add-on will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
