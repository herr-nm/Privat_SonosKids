#include <ESP8266WiFi.h>
#include <PubSubClient.h>
#include <SPI.h>
#include <MFRC522.h>

// WLAN- und MQTT-Konfiguration
const char* SSID = "SSID";
const char* PSK = "PASSWORD";
const char* MQTT_BROKER = "BROKERIP";
const int MQTT_PORT = 1883;

// Globale Objekte
WiFiClient espClient;
PubSubClient client(espClient);
#define SS_PIN D8
#define RST_PIN D3
#define LED_PIN D4
MFRC522 rfid(SS_PIN, RST_PIN);

// Variablen zur Tag-Verwaltung
String lastTag = "";
bool isPaused = false;

// Verbindungs-Setup
void setup() {
    Serial.begin(115200);
    setup_wifi();
    client.setServer(MQTT_BROKER, MQTT_PORT);
    SPI.begin();
    rfid.PCD_Init();
    pinMode(LED_PIN, OUTPUT);
    digitalWrite(LED_PIN, LOW);
}

void setup_wifi() {
    WiFi.begin(SSID, PSK);
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println("\nWLAN verbunden. IP: " + WiFi.localIP().toString());
}

void reconnect_mqtt() {
    while (!client.connected()) {
        Serial.print("MQTT-Verbindung...");
        if (client.connect("ESP8266Client")) {
            Serial.println("Verbunden.");
        } else {
            Serial.println("Fehler, erneuter Versuch...");
            delay(1000);
        }
    }
}

void loop() {
    if (WiFi.status() != WL_CONNECTED) setup_wifi();
    if (!client.connected()) reconnect_mqtt();
    client.loop();

    handleRFID();
    delay(100);
}

void handleRFID() {
    static bool tagOnReader = false;

    if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
        String currentTag = printHex(rfid.uid.uidByte, rfid.uid.size);

        if (!tagOnReader || currentTag != lastTag) {
            if (currentTag == lastTag && isPaused) {
                sendMQTTMessage("PLAY");
                isPaused = false;
                Serial.println("PLAY gesendet.");
            } else {
                sendMQTTMessage(currentTag);
                Serial.print("Tag-ID gesendet: ");
                Serial.println(currentTag);
            }
            lastTag = currentTag;
            tagOnReader = true;
            isPaused = false;
        }
        rfid.PICC_HaltA();
        rfid.PCD_StopCrypto1();
    } else if (tagOnReader) {
        // Tag wurde entfernt
        sendMQTTMessage("PAUSE");
        Serial.println("PAUSE gesendet.");
        tagOnReader = false;
        isPaused = true;
    }
}

void sendMQTTMessage(const String& message) {
    if (client.publish("/sonoskids/id", message.c_str())) {
        blinkLED();
    } else {
        Serial.println("Fehler beim Senden.");
    }
}

String printHex(byte *buffer, byte bufferSize) {
    String id = "";
    for (byte i = 0; i < bufferSize; i++) {
        id += buffer[i] < 0x10 ? "0" : "";
        id += String(buffer[i], HEX);
    }
    return id;
}

void blinkLED() {
    digitalWrite(LED_PIN, HIGH);
    delay(200);
    digitalWrite(LED_PIN, LOW);
}