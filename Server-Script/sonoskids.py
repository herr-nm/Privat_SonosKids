import mysql.connector
from mysql.connector import Error
import paho.mqtt.client as mqtt
import requests
import logging

# Logging konfigurieren
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# MQTT-Konfiguration
broker_IP_Adresse = "localhost"  # Adresse des MQTT-Brokers
broker_IP_Port = 1883            # Port des MQTT-Brokers
client_subscribe_topic = "/sonoskids/id"  # Topic zum Empfangen der RFID-Tags
client_connection_timeout = 60   # Timeout für die MQTT-Verbindung

# Sonos-Konfiguration
sonos_name_1 = "Schlafzimmer" # Name des Sonos-Lautsprechers, der angesteuert wird

# Globale Datenbankverbindung
connection = None

def get_connection():
    """
    Erstellt oder erneuert die MySQL-Datenbankverbindung.
    Vermeidet doppelte Verbindungen durch globale Nutzung.
    """
    global connection
    try:
        if connection is None or not connection.is_connected():
            connection = mysql.connector.connect(
                host="localhost",
                user="USER",
                passwd="PASSWORD",
                database="db_prod_sonoskids"
            )
            logger.info("Datenbankverbindung erfolgreich hergestellt")
    except Error as e:
        logger.error(f"Fehler beim Verbinden zur Datenbank: {e}")
    return connection

def execute_read_query(query, params=None):
    """
    Führt SELECT-Abfragen sicher aus und gibt das Ergebnis zurück.
    :param query: SQL-SELECT-Abfrage
    :param params: Parameter für die Abfrage (optional)
    :return: Ergebnis der Abfrage als Liste von Tupeln
    """
    try:
        conn = get_connection()
        with conn.cursor() as cursor:
            cursor.execute(query, params)
            result = cursor.fetchall()
        return result
    except Error as e:
        logger.error(f"Fehler bei SELECT-Abfrage: {e}")
        return []

def on_connect(client, userdata, flags, rc, properties=None):
    """
    Callback-Funktion bei erfolgreicher MQTT-Verbindung.
    :param client: MQTT-Client
    :param userdata: Benutzerdefinierte Daten
    :param flags: Verbindungsflags
    :param rc: Return Code der Verbindung
    :param properties: MQTTv5-Eigenschaften (optional)
    """
    logger.info("Verbindung zum MQTT-Broker hergestellt")
    client.subscribe(client_subscribe_topic)

def on_disconnect(client, userdata, rc):
    """
    Callback-Funktion bei Verbindungsverlust zum MQTT-Broker.
    :param client: MQTT-Client
    :param userdata: Benutzerdefinierte Daten
    :param rc: Return Code der Trennung
    """
    logger.warning("Verbindung verloren. Versuche erneut...")
    client.reconnect()

def on_message(client, userdata, msg):
    """
    Callback-Funktion, die auf eingehende Nachrichten auf dem abonnierten Topic reagiert.
    :param client: MQTT-Client
    :param userdata: Benutzerdefinierte Daten
    :param msg: Empfangene Nachricht
    """
    rfid_tag = msg.payload.decode()  # RFID-Tag aus der Nachricht auslesen
    logger.info(f"Empfangene RFID-ID: {rfid_tag}")
    
    # RFID-Karte suchen
    query = "SELECT k.typFK, t.bezeichnung, k.interpret, k.titel, k.linksuffix " \
            "FROM tbl_karte k JOIN tbl_typ t ON k.typFK = t.typPK WHERE k.kartePK = %s;"
    result = execute_read_query(query, (rfid_tag,))

    # Wenn RFID-Tag nicht gefunden wurde, nichts tun
    if not result:
        logger.info("RFID-Tag nicht bekannt. Keine Aktion durchgeführt.")
        return

    # RFID-Tag gefunden, Informationen aus der Datenbank laden
    typFK, typ_bezeichnung, interpret, titel, linksuffix = result[0]

    if not linksuffix:  # Wenn kein Inhalt hinterlegt wurde
        client.publish("/sonoskids/interpret", "")
        client.publish("/sonoskids/titel", "")
        client.publish("/sonoskids/status", "Bekannte Karte, aber noch ohne Inhalt")
        logger.info("Karte ohne Inhalte erkannt.")
        return

    # Spotify-Link abspielen
    if linksuffix.startswith("spotify/now/spotify:track") or linksuffix.startswith("spotify/now/spotify:album") or linksuffix.startswith("spotify/now/spotify:playlist"):
        play_spotify_link(linksuffix, interpret, titel, client)
    # Steuerkommandos an Sonos senden
    elif linksuffix.startswith("playpause") or linksuffix.startswith("volume") or linksuffix.startswith("sleep"):
        execute_sonos_command(linksuffix, client)
    else:
        logger.info(f"Unbekanntes Kommando oder Link: {linksuffix}")
        client.publish("/sonoskids/status", "Unbekanntes Kommando")

def play_spotify_link(linksuffix, interpret, titel, client):
    """
    Spielt einen Spotify-Link auf dem Sonos-Lautsprecher.
    :param linksuffix: Spotify-Link
    :param interpret: Interpret des Titels
    :param titel: Titelname
    :param client: MQTT-Client zur Statusrückmeldung
    """
    try:
        # Warteschlange löschen und neuen Inhalt abspielen
        requests.get(f'http://localhost:5005/{sonos_name_1}/clearqueue')
        response = requests.get(f'http://localhost:5005/{sonos_name_1}/{linksuffix}')
        
        # Status- und Titelinformationen zurückmelden
        client.publish("/sonoskids/interpret", interpret)
        client.publish("/sonoskids/titel", titel)

        if response.status_code == 200:
            client.publish("/sonoskids/status", "OK")
            logger.info("Spotify-Link erfolgreich abgespielt.")
        else:
            client.publish("/sonoskids/status", f"Fehler: {response.status_code}")
            logger.error(f"Sonos-Fehler: {response.status_code}")
    except Exception as e:
        logger.error(f"Fehler beim Abspielen auf Sonos: {e}")
        client.publish("/sonoskids/status", "Fehler beim Abspielen")

def execute_sonos_command(command, client):
    """
    Führt Steuerkommandos wie playpause, volume oder sleep aus.
    :param command: Kommando für die Sonos-API
    :param client: MQTT-Client zur Statusrückmeldung
    """
    try:
        response = requests.get(f'http://localhost:5005/{sonos_name_1}/{command}')
        if response.status_code == 200:
            client.publish("/sonoskids/status", "Steuerung erfolgreich")
            logger.info(f"Kommando '{command}' erfolgreich ausgeführt.")
        else:
            client.publish("/sonoskids/status", f"Fehler: {response.status_code}")
            logger.error(f"Fehler bei Kommando '{command}': {response.status_code}")
    except Exception as e:
        logger.error(f"Fehler bei Steuerkommando '{command}': {e}")
        client.publish("/sonoskids/status", "Fehler bei Steuerung")

# Einrichtung des MQTT-Clients
logger.info("Starte SonosKids...")
client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION2)
client.on_connect = on_connect
client.on_disconnect = on_disconnect
client.on_message = on_message

# Verbindung zum MQTT-Broker herstellen
client.connect(broker_IP_Adresse, broker_IP_Port, client_connection_timeout)
client.loop_forever()
