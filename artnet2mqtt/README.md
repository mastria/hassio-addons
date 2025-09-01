# Art-Net2MQTT Home Assistant Add-on

![Supports aarch64 Architecture][aarch64-shield]
![Supports amd64 Architecture][amd64-shield]
![Supports armv7 Architecture][armv7-shield]

_Receive Art-Net (DMX512) data and publish it to MQTT with Home Assistant auto-discovery._

## About

Art-Net2MQTT is a Home Assistant add-on that bridges Art-Net DMX512 lighting control protocol to MQTT. It receives Art-Net packets on UDP port 6454 and automatically creates Home Assistant sensors for each DMX channel, making it easy to monitor lighting equipment, stage effects, and other DMX-controlled devices directly in Home Assistant.

## Features

- 🎭 **Art-Net Protocol Support**: Receives Art-Net DMX512 data on standard UDP port 6454
- 🏠 **Home Assistant Integration**: Automatic sensor discovery with MQTT Discovery
- 🌐 **Multi-Universe Support**: Monitor universes 0-15 with configurable channel ranges
- ⚡ **High Performance**: Configurable throttling and change-only publishing to reduce MQTT traffic
- 📊 **Real-time Monitoring**: Live DMX channel values in Home Assistant
- 🔧 **Flexible Configuration**: Customize MQTT topics, device names, and monitoring behavior

## Installation

1. Add this repository to your Home Assistant Add-on Store:

[![Open your Home Assistant instance and show the add add-on repository dialog with a specific repository URL pre-filled.](https://my.home-assistant.io/badges/supervisor_add_addon_repository.svg)](https://my.home-assistant.io/redirect/supervisor_add_addon_repository/?repository_url=https://github.com/mastria/hassio-addons)

2. Install the "Art-Net2MQTT" add-on

3. Configure the add-on (see Configuration section below)

4. Start the add-on

## Configuration

### Basic Configuration

```yaml
log_level: info
mqtt:
  host: core-mosquitto
  port: 1883
  username: ""
  password: ""
discovery_prefix: homeassistant
node_name: artnet_bridge
universe: 0
start_channel: 1
channels: 20
throttle_ms: 20
object_prefix: artnet
ip_publish_interval_s: 30
publish_on_change_only: true
```

### Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `log_level` | list | `error` | Logging verbosity: trace, debug, info, notice, warning, error, fatal |
| `mqtt.host` | string | `core-mosquitto` | MQTT broker hostname or IP address |
| `mqtt.port` | int | `1883` | MQTT broker port |
| `mqtt.username` | string | - | MQTT username (optional) |
| `mqtt.password` | string | - | MQTT password (optional) |
| `discovery_prefix` | string | `homeassistant` | MQTT Discovery prefix for Home Assistant |
| `node_name` | string | `artnet_bridge` | Device name in Home Assistant |
| `universe` | int(0,15) | `0` | Art-Net universe to monitor |
| `start_channel` | int | `1` | First DMX channel to monitor (1-512) |
| `channels` | int | `20` | Number of channels to monitor |
| `throttle_ms` | int | `20` | Minimum time between MQTT publishes (milliseconds) |
| `object_prefix` | string | `artnet` | Prefix for MQTT topics and entity names |
| `ip_publish_interval_s` | int | `30` | Interval to publish Art-Net source IP information |
| `publish_on_change_only` | bool | `true` | Only publish when channel values change |

## Usage

### Setting up Art-Net Equipment

1. Configure your Art-Net lighting controller or software to send to your Home Assistant IP
2. Set the universe number in both your lighting software and the add-on configuration
3. Ensure your network allows UDP traffic on port 6454

### Monitoring in Home Assistant

Once configured and running, the add-on will automatically create sensors in Home Assistant:

- **DMX Channel Sensors**: `sensor.artnet_ch1`, `sensor.artnet_ch2`, etc.
- **Art-Net Info Sensor**: `sensor.artnet_info` (shows source IP and packet statistics)
- **Device Status**: Available under the configured device name

### Example Automations

Monitor a fog machine on channel 1:
```yaml
automation:
  - alias: "Fog Machine Alert"
    trigger:
      platform: numeric_state
      entity_id: sensor.artnet_ch1
      above: 0
    action:
      service: notify.mobile_app
      data:
        message: "Fog machine activated!"
```

Track lighting changes:
```yaml
automation:
  - alias: "Stage Lights Monitor"
    trigger:
      platform: state
      entity_id: 
        - sensor.artnet_ch1
        - sensor.artnet_ch2
        - sensor.artnet_ch3
    action:
      service: logbook.log
      data:
        name: "Stage Lighting"
        message: "Channel {{ trigger.entity_id.split('_')[1] }} changed to {{ trigger.to_state.state }}"
```

## Troubleshooting

### Common Issues

**Add-on won't start**
- Check that UDP port 6454 is available
- Verify MQTT broker is running and accessible
- Check add-on logs for specific error messages

**No Art-Net data received**
- Verify Art-Net equipment is sending to the correct IP address
- Check that universe numbers match between sender and add-on
- Ensure network firewall allows UDP port 6454
- Use `debug` log level to see packet reception details

**Sensors not appearing in Home Assistant**
- Verify MQTT Discovery is enabled in Home Assistant
- Check MQTT broker logs for connection issues
- Ensure `discovery_prefix` matches Home Assistant configuration

**Too many MQTT messages**
- Increase `throttle_ms` value
- Enable `publish_on_change_only`
- Reduce number of monitored `channels`

### Advanced Debugging

Enable debug logging:
```yaml
log_level: debug
```

This will show:
- Art-Net packet reception details
- MQTT publish operations
- Channel value changes
- Performance metrics

## Network Requirements

- **Protocol**: Art-Net over UDP
- **Port**: 6454 (standard Art-Net port)
- **Network**: Must be on same network segment as Art-Net equipment
- **Firewall**: Allow incoming UDP on port 6454

## Performance Considerations

- **High Channel Count**: Monitor only needed channels to reduce processing
- **Network Traffic**: Use `throttle_ms` and `publish_on_change_only` for busy networks
- **MQTT Load**: Consider dedicated MQTT broker for high-frequency updates
- **Memory Usage**: Each monitored channel uses minimal memory (~1KB per channel)

## Art-Net Compatibility

This add-on implements Art-Net 4 specification and is compatible with:

- **Lighting Consoles**: GrandMA, Hog, Chamsys, etc.
- **Software**: MadMapper, Resolume, TouchDesigner, QLC+, etc.
- **Media Servers**: Disguise, Watchout, Pandoras Box, etc.
- **LED Controllers**: PixLite, FPP, WLED (with Art-Net support), etc.

## Support

For issues, feature requests, or questions:

1. Check the [troubleshooting section](#troubleshooting)
2. Review add-on logs with debug logging enabled
3. Open an issue on [GitHub](https://github.com/mastria/hassio-addons-beta/issues)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Authors & Contributors

- **Mastria** - _Initial work_ - [mastria](https://github.com/mastria)

## Acknowledgments

- Art-Net protocol by Artistic Licence
- Home Assistant community
- Python Art-Net library contributors

---

[aarch64-shield]:https://img.shields.io/badge/aarch64-yes-green.svg
[amd64-shield]:https://img.shields.io/badge/amd64-yes-green.svg
[armv7-shield]:https://img.shields.io/badge/armv7-yes-green.svg