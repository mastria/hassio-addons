# CUPS AirPrint Add-on for Home Assistant

![Supports aarch64 Architecture][aarch64-shield]
![Supports amd64 Architecture][amd64-shield]
[![Home Assistant Add-on](https://img.shields.io/badge/home%20assistant-add--on-blue.svg)](https://www.home-assistant.io/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A comprehensive CUPS print server add-on with full AirPrint support for Home Assistant. Share USB printers connected to your Home Assistant device across your network, enabling seamless printing from iOS, macOS, Windows, Linux, and Android devices.

## Features

- 🖨️ **Full CUPS server** with web-based administration interface
- 🍎 **Native AirPrint/Bonjour** support for iOS and macOS
- 🔌 **Automatic USB printer detection** with plug-and-play support
- 🎨 **Wide driver support**: HP, Brother, Epson, Canon, Gutenprint, and more
- 🔒 **Secure access** via Home Assistant Ingress
- 📊 **Configurable logging** levels for easy troubleshooting
- 💾 **Persistent configuration** survives restarts and updates
- 🌐 **Network printing** via IPP (Internet Printing Protocol)

## Installation

[![Open your Home Assistant instance and show the add add-on repository dialog with a specific repository URL pre-filled.](https://my.home-assistant.io/badges/supervisor_add_addon_repository.svg)](https://my.home-assistant.io/redirect/supervisor_add_addon_repository/?repository_url=https://github.com/mastria/hassio-addons)

### Manual Installation

1. Go to **Settings** → **Add-ons** → **Add-on Store**
2. Click the menu (⋮) in the top right corner
3. Select **Repositories**
4. Add this repository URL:
   ```
   https://github.com/mastria/hassio-addons
   ```
5. Find "CUPS AirPrint" in the list and click **Install**
6. Wait for the installation to complete
7. Start the add-on

## Configuration

### Basic Configuration

```yaml
log_level: info
```

| Option | Values | Description |
|--------|--------|-------------|
| `log_level` | `debug`, `info`, `warning`, `error` | Set logging verbosity level |

### First-Time Setup

1. **Start the add-on** and wait for it to initialize
2. **Connect your USB printer** to your Home Assistant device
3. **Access the CUPS interface**:
   - Click **"OPEN WEB UI"** button in the add-on page, or
   - Navigate to: `http://homeassistant.local:631`
4. **Log in** with credentials:
   - Username: `print`
   - Password: `print`
5. **Add your printer**:
   - Go to **Administration** → **Add Printer**
   - Select your USB printer from the list
   - Choose the appropriate driver for your printer model
   - **Important:** Check **"Share This Printer"** to enable AirPrint
   - Set default options (paper size, quality, etc.)

## Usage

### Printing from iOS/iPadOS

1. Open any document, photo, or webpage
2. Tap the Share icon
3. Tap **Print**
4. Tap **Select Printer**
5. Your printer will appear automatically
6. Configure options and print

### Printing from macOS

1. Open **System Preferences** → **Printers & Scanners**
2. Click the **+** button
3. Your printer will appear with the AirPrint icon
4. Select and click **Add**

### Printing from Windows

1. Go to **Settings** → **Devices** → **Printers & scanners**
2. Click **Add a printer or scanner**
3. Click **The printer that I want isn't listed**
4. Select **Select a shared printer by name**
5. Enter: `http://homeassistant.local:631/printers/YOUR-PRINTER-NAME`

### Printing from Linux

Use CUPS or add via command line:

```bash
lpadmin -p MyPrinter -E -v ipp://homeassistant.local:631/printers/YOUR-PRINTER-NAME
```

## Technical Details

- **Base Image:** Debian 9.1.0
- **CUPS Version:** Latest stable from Debian repositories
- **Avahi:** Enabled for mDNS/Bonjour service discovery
- **Supported Architectures:** amd64, aarch64 (64-bit only)
- **Web Interface:** Port 631 (accessible via Ingress)
- **Configuration Path:** `/addon_configs/local_cups-airprint/cups/`

## Credentials

- **Default username:** `print`
- **Default password:** `print`

> ⚠️ **Security Note:** Change the default password after first login via the CUPS web interface under **Administration** → **Set Allowed Users**

## Included Drivers

- HP (HPLIP, HPCUPS)
- Brother (brlaser, ptouch)
- Epson
- Canon
- Gutenprint (wide compatibility)
- Generic PCL/PostScript
- And many more via OpenPrinting PPDs

## Troubleshooting

### Printer not detected

- Ensure printer is powered on and connected
- Check add-on logs with `log_level: debug`
- Verify USB connection in logs
- Try different USB port
- Restart the add-on

### AirPrint not working

- Ensure **"Share This Printer"** is enabled in CUPS
- Verify devices are on the same network
- Check firewall isn't blocking port 5353 (mDNS)
- Restart the add-on
- Restart iOS/macOS device

### Web interface not accessible

- Verify add-on is running
- Try accessing via IP: `http://[YOUR-HA-IP]:631`
- Clear browser cache
- Check add-on logs for errors

For more detailed troubleshooting, see [DOCS.md](DOCS.md)

## Support

- 📖 [Full Documentation](DOCS.md)
- 🐛 [Report Issues](https://github.com/mastria/hassio-addons/issues)
- 💬 [Home Assistant Community](https://community.home-assistant.io/)

## Credits

This add-on is built upon:

- [CUPS](https://www.cups.org/) - Common UNIX Printing System
- [Avahi](https://avahi.org/) - mDNS/DNS-SD service discovery
- [Home Assistant Community Add-ons](https://github.com/hassio-addons) - Base images

## License

MIT License - See [LICENSE](LICENSE) for details

---

**Made with ❤️ for the Home Assistant community**

[aarch64-shield]: https://img.shields.io/badge/aarch64-yes-green.svg
[amd64-shield]: https://img.shields.io/badge/amd64-yes-green.svg
