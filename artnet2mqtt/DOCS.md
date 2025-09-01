# Art-Net2MQTT Documentation

## Table of Contents

- [Overview](#overview)
- [Art-Net Protocol](#art-net-protocol)
- [Installation Guide](#installation-guide)
- [Configuration Reference](#configuration-reference)
- [MQTT Topics](#mqtt-topics)
- [Home Assistant Integration](#home-assistant-integration)
- [Performance Tuning](#performance-tuning)
- [Network Setup](#network-setup)
- [API Reference](#api-reference)
- [Examples](#examples)
- [Troubleshooting](#troubleshooting)

## Overview

Art-Net2MQTT is a bridge between the Art-Net DMX512 lighting protocol and MQTT, designed specifically for Home Assistant integration. It enables real-time monitoring of lighting equipment, stage effects, and other DMX-controlled devices within your smart home ecosystem.

### Architecture

```
┌─────────────────┐    Art-Net UDP    ┌──────────────────┐    MQTT    ┌─────────────────┐
│ Lighting        │ ────────────────► │ Art-Net2MQTT     │ ─────────► │ Home Assistant  │
│ Controller      │    Port 6454      │ Add-on           │            │ + MQTT Broker   │
└─────────────────┘                   └──────────────────┘            └─────────────────┘
```

## Art-Net Protocol

### What is Art-Net?

Art-Net is an Ethernet protocol based on the TCP/IP protocol suite. Its purpose is to allow transfer of large amounts of DMX512 data over a wide area using standard networking technology.

### Key Concepts

- **Universe**: A collection of 512 DMX channels (similar to a DMX universe)
- **Channel**: Individual control value (0-255) for a device parameter
- **Node**: A device that can send or receive Art-Net data
- **Port**: Physical or virtual connection point (6454/UDP for Art-Net)

### Supported Features

- Art-Net 4 protocol compliance
- Universe 0-15 monitoring
- Real-time DMX data reception
- Automatic source detection
- Broadcast and unicast support

## Installation Guide

### Prerequisites

- Home Assistant OS/Supervised/Container
- MQTT broker (Mosquitto recommended)
- Network access to Art-Net equipment
- Available UDP port 6454

### Step-by-Step Installation

1. **Add Repository**
   ```
   Settings → Add-ons → Add-on Store → ⋮ → Repositories
   Add: https://github.com/mastria/hassio-addons-beta
   ```

2. **Install Add-on**
   - Find "Art-Net2MQTT" in the store
   - Click "INSTALL"
   - Wait for installation to complete

3. **Basic Configuration**
   ```yaml
   mqtt:
     host: core-mosquitto
     port: 1883
   universe: 0
   start_channel: 1
   channels: 10
   ```

4. **Start Add-on**
   - Enable "Start on boot" if desired
   - Click "START"
   - Monitor logs for successful startup

## Configuration Reference

### MQTT Configuration

```yaml
mqtt:
  host: core-mosquitto        # MQTT broker hostname
  port: 1883                  # MQTT broker port
  username: ""                # Optional authentication
  password: ""                # Optional authentication
```

**Notes:**
- Leave username/password empty for anonymous access
- Use `core-mosquitto` for Home Assistant Mosquitto add-on
- For external brokers, use IP address or hostname

### Art-Net Configuration

```yaml
universe: 0                   # Art-Net universe (0-15)
start_channel: 1              # First DMX channel to monitor
channels: 20                  # Number of channels to monitor
```

**Channel Mapping:**
- DMX channels are 1-indexed (1-512)
- Total monitored range: `start_channel` to `start_channel + channels - 1`
- Maximum 512 channels per universe

### Performance Configuration

```yaml
throttle_ms: 20               # Minimum time between MQTT publishes
publish_on_change_only: true  # Only publish when values change
ip_publish_interval_s: 30     # Art-Net info update interval
```

**Optimization Guidelines:**
- Increase `throttle_ms` for busy networks (20-100ms)
- Enable `publish_on_change_only` to reduce MQTT traffic
- Reduce `channels` count to minimize processing

### Discovery Configuration

```yaml
discovery_prefix: homeassistant  # MQTT Discovery prefix
node_name: artnet_bridge        # Device name in Home Assistant
object_prefix: artnet           # Entity name prefix
```

**Entity Naming:**
- Channels: `sensor.{object_prefix}_ch{number}`
- Info: `sensor.{object_prefix}_info`
- Device: `{node_name}`

### Logging Configuration

```yaml
log_level: error  # trace, debug, info, notice, warning, error, fatal
```

**Log Levels:**
- `trace`/`debug`: Detailed packet and processing information
- `info`: Normal operation messages
- `warning`: Only warnings and errors
- `error`: Only error messages (recommended)
- `fatal`: Only critical errors

## MQTT Topics

### Channel Topics

```
{object_prefix}/channel/{channel_number}/state
```

**Example:**
```
artnet/channel/1/state → "255"
artnet/channel/2/state → "128"
artnet/channel/3/state → "0"
```

### Info Topic

```
{object_prefix}/info/state
```

**Payload Example:**
```json
{
  "source_ip": "192.168.1.100",
  "universe": 0,
  "packets_received": 1247,
  "last_packet": "2025-09-01T10:30:45Z"
}
```

### Availability Topic

```
{node_name}/status
```

**Values:**
- `online`: Add-on is running and receiving data
- `offline`: Add-on is stopped or disconnected

## Home Assistant Integration

### Automatic Discovery

The add-on automatically creates entities using MQTT Discovery:

```yaml
# Generated discovery config for channel 1
sensor:
  name: "Art-Net Channel 1"
  state_topic: "artnet/channel/1/state"
  availability_topic: "artnet_bridge/status"
  device:
    name: "Art-Net Bridge"
    identifiers: ["artnet_bridge"]
    manufacturer: "mastria"
    model: "Art-Net2MQTT"
```

### Entity Attributes

Each channel sensor includes:
- **State**: Current DMX value (0-255)
- **Last Updated**: Timestamp of last change
- **Device Info**: Art-Net bridge information
- **Availability**: Online/offline status

### Device Registration

All sensors are grouped under a single device:
- **Name**: Configured `node_name`
- **Manufacturer**: mastria
- **Model**: Art-Net2MQTT
- **Version**: Add-on version

## Performance Tuning

### High-Frequency Scenarios

For lighting controllers sending at 30-40 FPS:

```yaml
throttle_ms: 33               # ~30 FPS max
publish_on_change_only: true  # Reduce redundant updates
channels: 10                  # Monitor only needed channels
```

### Low-Latency Requirements

For real-time monitoring:

```yaml
throttle_ms: 0                # No throttling
publish_on_change_only: false # All updates
log_level: warning            # Reduce log overhead
```

### Network Bandwidth Optimization

```yaml
throttle_ms: 100              # 10 updates/second max
publish_on_change_only: true  # Change detection
ip_publish_interval_s: 300    # 5-minute info updates
```

### Memory Usage

- Base usage: ~10MB
- Per channel: ~1KB additional
- Max recommended: 100 channels simultaneously

## Network Setup

### Firewall Configuration

**Home Assistant Host:**
```bash
# Allow Art-Net input
iptables -A INPUT -p udp --dport 6454 -j ACCEPT
```

**Router/Switch:**
- Ensure Art-Net equipment and Home Assistant are on same VLAN
- No NAT between Art-Net source and Home Assistant
- Consider multicast support for broadcast Art-Net

### Art-Net Equipment Setup

**Common Settings:**
- Output IP: Home Assistant IP address
- Output Port: 6454
- Universe: Match add-on configuration
- Protocol: Art-Net v4

**Software Examples:**

*QLC+:*
```
Output → Art-Net → Add Universe
- Universe: 0
- Mode: Output
- Address: {HA_IP}:6454
```

*TouchDesigner:*
```
DMX Out CHOP → Art-Net
- Active: On
- Universe: 0
- IP Address: {HA_IP}
```

## API Reference

### Configuration Schema

```yaml
log_level: list(trace|debug|info|notice|warning|error|fatal)
mqtt:
  host: str
  port: int
  username: str?
  password: str?
discovery_prefix: str
node_name: str
universe: int(0,15)
start_channel: int(1,512)
channels: int(1,512)
throttle_ms: int(0,1000)
object_prefix: str
ip_publish_interval_s: int(1,3600)
publish_on_change_only: bool
```

### MQTT Message Format

**Channel State:**
```json
{
  "topic": "artnet/channel/1/state",
  "payload": "255",
  "qos": 0,
  "retain": false
}
```

**Info State:**
```json
{
  "topic": "artnet/info/state",
  "payload": "{\"source_ip\":\"192.168.1.100\",\"universe\":0,\"packets_received\":1247}",
  "qos": 0,
  "retain": false
}
```

## Examples

### Basic Lighting Monitor

Monitor 8 dimmers on universe 0:

```yaml
universe: 0
start_channel: 1
channels: 8
throttle_ms: 50
publish_on_change_only: true
```

### RGB LED Strip Control

Monitor RGB values on channels 10-12:

```yaml
universe: 0
start_channel: 10
channels: 3
object_prefix: rgb_strip
throttle_ms: 20
```

### Multi-Universe Setup

For multiple universes, run multiple add-on instances:

**Instance 1 (Universe 0):**
```yaml
universe: 0
node_name: artnet_universe_0
object_prefix: artnet_u0
```

**Instance 2 (Universe 1):**
```yaml
universe: 1
node_name: artnet_universe_1
object_prefix: artnet_u1
```

### Home Assistant Automations

**Fog Machine Activation:**
```yaml
automation:
  - alias: "Fog Machine Detection"
    trigger:
      platform: numeric_state
      entity_id: sensor.artnet_ch5
      above: 50
    action:
      - service: notify.mobile_app_phone
        data:
          message: "Fog machine activated at {{ states('sensor.artnet_ch5') }}"
```

**Color Temperature Tracking:**
```yaml
automation:
  - alias: "Lighting Color Sync"
    trigger:
      platform: state
      entity_id: 
        - sensor.artnet_ch1  # Red
        - sensor.artnet_ch2  # Green
        - sensor.artnet_ch3  # Blue
    action:
      - service: light.turn_on
        target:
          entity_id: light.ambient_rgb
        data:
          rgb_color:
            - "{{ states('sensor.artnet_ch1') | int }}"
            - "{{ states('sensor.artnet_ch2') | int }}"
            - "{{ states('sensor.artnet_ch3') | int }}"
```

## Troubleshooting

### Connection Issues

**Symptom**: No Art-Net data received
**Solutions**:
1. Verify network connectivity: `ping {artnet_source_ip}`
2. Check Art-Net source configuration
3. Monitor with packet capture: `tcpdump -i eth0 udp port 6454`
4. Ensure universe numbers match

**Symptom**: MQTT connection failed
**Solutions**:
1. Test MQTT broker: `mosquitto_pub -h {broker} -t test -m hello`
2. Check credentials and network access
3. Verify broker is running and accessible

### Performance Issues

**Symptom**: High CPU usage
**Solutions**:
1. Increase `throttle_ms` value
2. Reduce `channels` count
3. Enable `publish_on_change_only`
4. Use `warning` log level

**Symptom**: Missing updates
**Solutions**:
1. Decrease `throttle_ms` value
2. Disable `publish_on_change_only`
3. Check network packet loss
4. Monitor add-on logs for errors

### Configuration Issues

**Symptom**: Entities not appearing
**Solutions**:
1. Check MQTT Discovery is enabled in HA
2. Verify `discovery_prefix` matches HA config
3. Restart Home Assistant after configuration changes
4. Check MQTT broker logs

**Symptom**: Wrong channel values
**Solutions**:
1. Verify `start_channel` configuration
2. Check Art-Net universe mapping
3. Compare with Art-Net source configuration
4. Use debug logging to trace values

For additional support, please check the GitHub repository issues or create a new issue with:
- Add-on version
- Home Assistant version
- Full configuration (sanitized)
- Relevant log entries
- Network setup description
