#!/usr/bin/env python3

"""
ArtNet to MQTT for Home Assistant add-on.
- Receives ArtDMX (UDP 6454) via a pure-Python UDP socket (no native deps).
- Publishes per-channel sensors via Home Assistant MQTT Discovery.
- LWT/availability, publish-on-change, optional per-channel throttle.
"""
import os
import json
import time
import socket
import logging
import sys
from collections import defaultdict
from threading import Thread, Event
import struct
import paho.mqtt.client as mqtt

# ---------------------------------------------------------------------------
# Pure-Python Art-Net (ArtDMX) listener — no native dependencies
# ---------------------------------------------------------------------------
ARTNET_PORT = 6454
_ARTNET_ID = b"Art-Net\x00"
_OPCODE_DMX = 0x5000  # little-endian on the wire → 0x00 0x50


class _ArtNetPacket:
    """Minimal ArtDMX packet representation."""

    __slots__ = ("universe", "data", "sequence", "physical")

    def __init__(self, universe: int, data: bytes, sequence: int = 0, physical: int = 0):
        self.universe = universe
        self.data = data
        self.sequence = sequence
        self.physical = physical


class _ArtNetListener:
    """
    Thin UDP listener that parses ArtDMX packets.

    Art-Net packet layout (ArtDmx):
      0-7   : "Art-Net\0"  (ID)
      8-9   : OpCode 0x5000 (LE)
      10-11 : ProtVer (BE, must be >= 14)
      12    : Sequence
      13    : Physical
      14-15 : Universe (LE, 15-bit)
      16-17 : Length (BE)
      18+   : DMX data
    """

    def __init__(self, host: str = "0.0.0.0", port: int = ARTNET_PORT):
        self._sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self._sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self._sock.bind((host, port))

    def readPacket(self, timeout: float = 0.05) -> "_ArtNetPacket | None":
        """Return next ArtDMX packet or None on timeout."""
        self._sock.settimeout(timeout)
        try:
            raw, _ = self._sock.recvfrom(65535)
        except socket.timeout:
            return None
        except OSError:
            return None
        return self._parse(raw)

    @staticmethod
    def _parse(data: bytes) -> "_ArtNetPacket | None":
        if len(data) < 18:
            return None
        if data[:8] != _ARTNET_ID:
            return None
        opcode = struct.unpack_from("<H", data, 8)[0]
        if opcode != _OPCODE_DMX:
            return None
        sequence = data[12]
        physical = data[13]
        universe = struct.unpack_from("<H", data, 14)[0]
        length = struct.unpack_from(">H", data, 16)[0]
        dmx_data = data[18: 18 + length]
        return _ArtNetPacket(universe, dmx_data, sequence, physical)

    def close(self):
        try:
            self._sock.close()
        except OSError:
            pass

# Configure logging (will be updated based on config)
logging.basicConfig(
    level=logging.INFO,  # Default level, will be updated
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)],
)
logger = logging.getLogger(__name__)


class ArtNet2MQTT:
    def __init__(self):
        """Initialize the ArtNet to MQTT bridge."""
        self.config = self._load_config()
        self._setup_logging()
        self._setup_mqtt()
        self._setup_artnet()
        self.last_value = {}
        self.last_pub_ms = defaultdict(lambda: 0)
        self.stop_event = Event()

    def _load_config(self):
        """Load configuration from Home Assistant."""
        try:
            with open("/data/options.json", "r") as f:
                config = json.load(f)
        except FileNotFoundError:
            logger.error("Configuration file not found!")
            sys.exit(1)
        except json.JSONDecodeError as e:
            logger.error(f"Invalid JSON in configuration: {e}")
            sys.exit(1)

        # MQTT
        mqtt_config = config.get("mqtt", {})
        self.mqtt_host = os.getenv("MQTT_HOST", mqtt_config.get("host", "core-mosquitto"))
        self.mqtt_port = int(os.getenv("MQTT_PORT", mqtt_config.get("port", 1883)))
        self.mqtt_user = os.getenv("MQTT_USER", mqtt_config.get("username")) or None
        self.mqtt_pass = os.getenv("MQTT_PASS", mqtt_config.get("password")) or None

        # Discovery / device
        self.discovery_prefix = config.get("discovery_prefix", "homeassistant")
        self.node_name = config.get("node_name", "artnet_bridge")
        self.object_prefix = config.get("object_prefix", "artnet")

        # Art-Net
        self.universe = int(config.get("universe", 0))
        self.start_channel = int(config.get("start_channel", 1))
        self.total_channels = int(config.get("channels", 20))

        # Throttle / behavior
        self.throttle_ms = int(config.get("throttle_ms", 20))
        self.publish_on_change_only = bool(config.get("publish_on_change_only", True))
        self.ip_publish_interval_s = int(config.get("ip_publish_interval_s", 30))

        # Sensor extras
        self.force_update = bool(config.get("force_update", True))
        self.expire_after = config.get("expire_after", None)  # seconds or None

        # Topics
        self.availability_topic = f"{self.node_name}/status"

        # Validate DMX range (512 channels per universe)
        if not (0 <= self.universe <= 15):
            raise ValueError("universe must be in 0..15")
        if not (1 <= self.start_channel <= 512):
            raise ValueError("start_channel must be in 1..512")
        if not (1 <= self.total_channels <= 512):
            raise ValueError("channels must be in 1..512")
        if (self.start_channel - 1) + self.total_channels > 512:
            raise ValueError("start_channel + channels - 1 cannot exceed 512")

        logger.info(
            "Configuration loaded - Universe: %s, Channels: %s-%s",
            self.universe,
            self.start_channel,
            self.start_channel + self.total_channels - 1,
        )
        logger.info(f"🎯 Will monitor universe {self.universe}, channels {self.start_channel} to {self.start_channel + self.total_channels - 1}")

        return config

    def _setup_logging(self):
        """Configure logging based on user's log level setting."""
        log_level = self.config.get("log_level", "info").upper()
        
        # Map log level strings to logging constants
        level_map = {
            "TRACE": logging.DEBUG,    # Trace maps to DEBUG in Python
            "DEBUG": logging.DEBUG,
            "INFO": logging.INFO,
            "NOTICE": logging.INFO,    # Notice maps to INFO in Python
            "WARNING": logging.WARNING,
            "ERROR": logging.ERROR,
            "FATAL": logging.CRITICAL  # Fatal maps to CRITICAL in Python
        }
        
        numeric_level = level_map.get(log_level, logging.INFO)
        
        # Update the root logger level
        logging.getLogger().setLevel(numeric_level)
        
        # Update all handlers
        for handler in logging.getLogger().handlers:
            handler.setLevel(numeric_level)
            
        logger.info(f"📋 Log level set to: {log_level}")

    def _setup_mqtt(self):
        """Setup MQTT client."""
        self.client = mqtt.Client(
            client_id=self.node_name,
            callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
        )
        if self.mqtt_user:
            self.client.username_pw_set(self.mqtt_user, self.mqtt_pass)

        # LWT
        self.client.will_set(self.availability_topic, "offline", qos=1, retain=True)

        # Callbacks
        self.client.on_connect = self._on_mqtt_connect
        self.client.on_disconnect = self._on_mqtt_disconnect
        self.client.on_message = self._on_mqtt_message

        # Robustez
        self.client.enable_logger(logger)
        self.client.reconnect_delay_set(min_delay=1, max_delay=30)

    def _setup_artnet(self):
        """Setup ArtNet receiver (pure-Python UDP socket, no native deps)."""
        self.artnet = _ArtNetListener()
        logger.info("ArtNet UDP listener bound to 0.0.0.0:%d", ARTNET_PORT)

    # ---------- MQTT callbacks ----------

    def _on_mqtt_connect(self, client, userdata, flags, reason_code, properties):
        """Callback for MQTT connection (Paho v2 signature)."""
        if reason_code == 0:
            logger.info("Connected to MQTT broker %s:%s", self.mqtt_host, self.mqtt_port)
            client.publish(self.availability_topic, "online", qos=1, retain=True)
            # Reenviar discovery quando o HA voltar
            client.subscribe("homeassistant/status", qos=0)
            self._publish_discovery()
        else:
            logger.error("Failed to connect to MQTT broker: %s", reason_code)

    def _on_mqtt_disconnect(self, client, userdata, flags, reason_code, properties):
        """Callback for MQTT disconnection."""
        logger.warning("Disconnected from MQTT broker: %s", reason_code)

    def _on_mqtt_message(self, client, userdata, msg):
        """Handle misc MQTT messages (e.g., HA birth)."""
        if msg.topic == "homeassistant/status":
            payload = (msg.payload or b"").decode(errors="ignore").strip().lower()
            if payload == "online":
                logger.info("Home Assistant is online; re-publishing discovery.")
                self._publish_discovery()

    # ---------- Discovery ----------

    def _publish_discovery(self):
        """Publish Home Assistant MQTT Discovery configuration."""
        logger.info("Publishing MQTT Discovery configuration...")

        device = {
            "identifiers": [self.node_name],
            "name": "ArtNet Bridge",
            "manufacturer": "mastria.dev.br",
            "model": "ArtNet to MQTT Bridge",
            "sw_version": "1.0.0",
        }

        # Bridge IP sensor (diagnóstico)
        ip_unique_id = f"{self.node_name}_ip"
        ip_object_id = f"{self.object_prefix}_eth_ip"
        ip_discovery_topic = f"{self.discovery_prefix}/sensor/{ip_object_id}/config"
        ip_config = {
            "name": "Bridge IP Address",
            "unique_id": ip_unique_id,
            "state_topic": f"{self.node_name}/eth/ip",
            "availability_topic": self.availability_topic,
            "device": device,
            "icon": "mdi:ip-network",
            "entity_category": "diagnostic",
        }
        self.client.publish(ip_discovery_topic, json.dumps(ip_config), retain=True, qos=1)

        # DMX channel sensors
        for ch in range(self.start_channel, self.start_channel + self.total_channels):
            unique_id = f"{self.node_name}_u{self.universe}_ch{ch}"
            object_id = f"{self.object_prefix}_u{self.universe}_ch{ch}"
            discovery_topic = f"{self.discovery_prefix}/sensor/{object_id}/config"

            config = {
                "name": f"DMX U{self.universe} CH{ch}",
                "unique_id": unique_id,
                "state_topic": f"{self.node_name}/u/{self.universe}/ch/{ch}",
                "availability_topic": self.availability_topic,
                "device": device,
                "state_class": "measurement",
                "icon": "mdi:lightbulb-on",
                "force_update": self.force_update,
            }
            if isinstance(self.expire_after, int) and self.expire_after > 0:
                config["expire_after"] = self.expire_after

            self.client.publish(discovery_topic, json.dumps(config), retain=True, qos=1)

        logger.info("Published discovery for %d DMX channels", self.total_channels)

    # ---------- Helpers ----------

    def _get_local_ip(self):
        """Get local IP address."""
        try:
            with socket.socket(socket.AF_INET, socket.SOCK_DGRAM) as s:
                s.connect(("8.8.8.8", 80))
                return s.getsockname()[0]
        except Exception:
            return "0.0.0.0"

    def _ip_publisher_thread(self):
        """Thread to publish IP address periodically."""
        last_sent = ""
        while not self.stop_event.is_set():
            try:
                current_ip = self._get_local_ip()
                if current_ip != last_sent:
                    self.client.publish(f"{self.node_name}/eth/ip", current_ip, qos=1, retain=True)
                    logger.debug("Published IP: %s", current_ip)
                    last_sent = current_ip
            except Exception as e:
                logger.error("Error publishing IP: %s", e)

            self.stop_event.wait(max(5, self.ip_publish_interval_s))

    # ---------- DMX publishing policy ----------

    def _should_publish(self, channel, value):
        """Check if channel value should be published based on throttling and change detection."""
        now = int(time.time() * 1000)
        prev = self.last_value.get(channel)

        # Se change-only estiver ativo, não republicar o mesmo valor
        if self.publish_on_change_only and prev is not None and prev == value:
            return False

        if self.throttle_ms > 0:
            if (now - self.last_pub_ms[channel]) < self.throttle_ms:
                return False

        self.last_value[channel] = value
        self.last_pub_ms[channel] = now
        return True

    # ---------- Art-Net handling ----------

    def _on_artnet_frame(self, frame):
        """Handle incoming ArtNet frame (expects .universe and .data)."""
        # Filtra universos diferentes do configurado
        if frame.universe != self.universe:
            logger.debug(f"🔄 Filtering out frame from universe {frame.universe} (configured: {self.universe})")
            return

        dmx_data = frame.data
        published_count = 0
        publications = []

        logger.debug(f"📡 Processing frame with {len(dmx_data)} DMX channels for universe {frame.universe}")

        for i in range(self.total_channels):
            channel = self.start_channel + i
            dmx_index = channel - 1

            if dmx_index >= len(dmx_data):
                logger.debug(f"⚠️  Channel {channel} (index {dmx_index}) exceeds DMX data length {len(dmx_data)}")
                break

            value = int(dmx_data[dmx_index])

            if self._should_publish(channel, value):
                topic = f"{self.node_name}/u/{self.universe}/ch/{channel}"
                try:
                    # QoS 0 and no retain for high volume
                    self.client.publish(topic, str(value), qos=0, retain=False)
                    published_count += 1
                    publications.append(f"CH{channel}={value}")
                    
                    # Log individual for initial publications
                    if published_count <= 5:
                        logger.info(f"📤 Published: {topic} = {value}")
                        
                except Exception as e:
                    logger.error("Error publishing channel %s: %s", channel, e)

        if published_count > 0:
            logger.info(f"✅ Published {published_count} channel updates: {', '.join(publications[:10])}{'...' if len(publications) > 10 else ''}")
        else:
            logger.debug("🔄 No channels needed publishing (throttle/change-only active)")

        # Periodic logging of current values even if not published
        if hasattr(self, '_last_debug_log'):
            if time.time() - self._last_debug_log > 10:  # Every 10 seconds
                current_values = []
                for i in range(min(self.total_channels, 10)):
                    channel = self.start_channel + i
                    dmx_index = channel - 1
                    if dmx_index < len(dmx_data):
                        value = int(dmx_data[dmx_index])
                        current_values.append(f"CH{channel}={value}")
                logger.info(f"📊 Current values: {', '.join(current_values)}")
                self._last_debug_log = time.time()
        else:
            self._last_debug_log = time.time()

    def _artnet_listener_thread(self):
        """Thread to handle ArtNet listening."""
        logger.info("Starting ArtNet listener thread...")
        packet_count = 0
        universe_packets: defaultdict = defaultdict(int)
        try:
            while not self.stop_event.is_set():
                pkt = self.artnet.readPacket(timeout=0.05)
                if pkt is None:
                    continue

                pkt_universe = pkt.universe
                universe_packets[pkt_universe] += 1
                logger.debug("Received packet from universe %s", pkt_universe)

                if pkt_universe != self.universe:
                    continue

                packet_count += 1
                self._process_artnet_packet(pkt)

                if packet_count % 50 == 0:
                    logger.info(
                        "📊 Processed %d packets. Universe stats: %s",
                        packet_count,
                        dict(universe_packets),
                    )

        except Exception as e:
            logger.error("Error in ArtNet listener: %s", e)
        finally:
            logger.info(
                "ArtNet listener stopped. Total: %d, Universes: %s",
                packet_count,
                dict(universe_packets),
            )

    def _process_artnet_packet(self, packet):
        """Process received ArtNet packet into a SimpleFrame and dispatch."""
        try:
            if not packet:
                return

            data_array = None
            universe = self.universe  # default

            # Common extraction attempts (ArtnetPacket usually has data/universe)
            if hasattr(packet, "data"):
                data_array = packet.data
                logger.debug(f"Extracted data from packet.data: {len(data_array) if data_array else 0} bytes")
            if hasattr(packet, "universe"):
                universe = packet.universe
                logger.debug(f"Extracted universe: {universe}")

            # Alternatives
            if data_array is None and hasattr(packet, "dmx_data"):
                data_array = packet.dmx_data
                logger.debug(f"Extracted data from packet.dmx_data: {len(data_array) if data_array else 0} bytes")
            if data_array is None and hasattr(packet, "payload"):
                data_array = packet.payload
                logger.debug(f"Extracted data from packet.payload: {len(data_array) if data_array else 0} bytes")

            # Fallback using converter if available
            if data_array is None and hasattr(self.artnet, "artnet_packet_to_array"):
                try:
                    data_array = self.artnet.artnet_packet_to_array(packet)
                    logger.debug(f"Converted with artnet_packet_to_array: {len(data_array) if data_array else 0} bytes")
                except Exception as e:
                    logger.debug(f"Failed to convert with artnet_packet_to_array: {e}")

            if not data_array:
                logger.warning("❌ No data found in packet; cannot process.")
                logger.debug(f"Packet attributes: {[attr for attr in dir(packet) if not attr.startswith('_')]}")
                return

            # Ignore packets from different universes than configured
            if universe != self.universe:
                logger.debug(f"🔄 Ignoring packet from universe {universe} (configured: {self.universe})")
                return

            class SimpleFrame:
                def __init__(self, universe, data):
                    self.universe = universe
                    self.data = data

            frame = SimpleFrame(universe, data_array)
            
            # Detailed log of the channels we will monitor
            channels_preview = []
            for i in range(min(self.total_channels, 10)):  # Show up to 10 channels
                dmx_index = (self.start_channel + i) - 1
                if dmx_index < len(data_array):
                    value = int(data_array[dmx_index])
                    channels_preview.append(f"CH{self.start_channel + i}={value}")
            
            logger.info(f"🎯 Processing ArtNet frame - Universe: {universe}, Total channels: {len(data_array)}")
            logger.info(f"📊 Monitored channels ({self.start_channel}-{self.start_channel + self.total_channels - 1}): {', '.join(channels_preview)}{'...' if self.total_channels > 10 else ''}")
            
            self._on_artnet_frame(frame)

        except Exception as e:
            logger.error("Error processing ArtNet packet: %s", e)
            logger.debug("Packet details - Type: %s", type(packet))

    # ---------- Lifecycle ----------

    def start(self):
        """Start the ArtNet to MQTT bridge."""
        try:
            # MQTT
            logger.info("Connecting to MQTT...")
            self.client.connect(self.mqtt_host, self.mqtt_port, keepalive=60)
            self.client.loop_start()

            # Auxiliary threads
            Thread(target=self._ip_publisher_thread, daemon=True).start()
            Thread(target=self._artnet_listener_thread, daemon=True).start()

            logger.info("Art-Net2MQTT bridge is running...")
            while not self.stop_event.is_set():
                self.stop_event.wait(1)

        except KeyboardInterrupt:
            logger.info("Received interrupt signal")
        except Exception as e:
            logger.error("Unexpected error: %s", e)
        finally:
            self.stop()

    def stop(self):
        """Stop the Art-Net2MQTT bridge."""
        logger.info("Stopping Art-Net2MQTT bridge...")
        self.stop_event.set()

        try:
            if hasattr(self, "artnet"):
                # python_artnet public API
                if hasattr(self.artnet, "close"):
                    self.artnet.close()
                logger.info("ArtNet listener closed")
        except Exception as e:
            logger.warning("Error closing ArtNet listener: %s", e)

        try:
            if hasattr(self, "client"):
                self.client.publish(self.availability_topic, "offline", qos=1, retain=True)
                self.client.loop_stop()
                self.client.disconnect()
                logger.info("MQTT client disconnected")
        except Exception as e:
            logger.warning("Error stopping MQTT client: %s", e)


def main():
    """Main entry point."""
    logger.info("=== Art-Net2MQTT Bridge Starting ===")
    bridge = ArtNet2MQTT()
    try:
        bridge.start()
    except Exception as e:
        logger.error("Failed to start bridge: %s", e)
        sys.exit(1)


if __name__ == "__main__":
    main()