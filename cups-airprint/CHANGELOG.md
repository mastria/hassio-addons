## v1.1.0

### 🚀 Improvements

- **Base update:** Migrated to Debian Base 9.1.0
- **Architectures:** Support only for 64-bit (aarch64, amd64) - deprecated: i386, armhf, armv7
- **Enhanced USB access:** Configured `usb: true` and `udev: true` for better device detection
- **Optimized privileges:** Using specific privileges (`SYS_RAWIO`, `NET_ADMIN`, `NET_RAW`) instead of `full_access`
- **Improved AirPrint support:** Proper Avahi configuration with `host_dbus: true`
- **Configurable logs:** `log_level` option to adjust log level (debug/info/warning/error)
- **Improved startup script:** Better process management and error detection
- **Enhanced persistence:** Data saved in `/addon_configs` following best practices

### 📦 New Drivers

- `printer-driver-brlaser` - Brother printers
- `printer-driver-c2esp` - Kodak printers
- `printer-driver-ptouch` - Brother P-Touch label makers
- `cups-filters` and `cups-browsed` - Better compatibility

### 🔧 Configuration

- Configuration via addon `options`
- Support for `addon_config:rw` for advanced configurations
- CUPS web interface on port 631
- Automatic USB printer detection

### 🐛 Fixes

- Better error handling in startup script
- Fixed startup order (D-Bus → Avahi → CUPS)
- Correct permissions for `print` user
- Proper binding of persistent directories

### 🔒 Security

- Removal of unnecessary packages (gnupg2, samba, etc)
- AppArmor kept enabled
- Minimum required privileges
- No use of `full_access`

## v1.0.0

- Debian Base 7.8.3
- Basic functional CUPS
- HP drivers (printer-driver-hpcups)
- Basic AirPrint support
- Web interface on port 631

