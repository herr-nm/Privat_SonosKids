# Projekt: SonosKids - Eine DIY "TonieBox" mit Sonos, Spotify und RFID-Tags

## Inhaltsverzeichnis
- [Projekt: SonosKids - Eine DIY "TonieBox" mit Sonos, Spotify und RFID-Tags](#projekt-sonoskids---eine-diy-toniebox-mit-sonos-spotify-und-rfid-tags)
  - [Inhaltsverzeichnis](#inhaltsverzeichnis)
  - [1. Einleitung](#1-einleitung)
  - [2. Projektbeschreibung](#2-projektbeschreibung)
  - [3. Systemarchitektur](#3-systemarchitektur)
  - [4. Datenbankstruktur](#4-datenbankstruktur)
    - [Tabellenstruktur](#tabellenstruktur)
    - [Beispieldaten](#beispieldaten)
  - [5. Software-Komponenten](#5-software-komponenten)
  - [6. Ablaufbeschreibung](#6-ablaufbeschreibung)
  - [7. Hardware-Anforderungen](#7-hardware-anforderungen)
  - [8. Installation und Konfiguration](#8-installation-und-konfiguration)
    - [8.1 MariaDB installieren und einrichten](#81-mariadb-installieren-und-einrichten)
    - [8.2 MQTT-Broker installieren (Mosquitto)](#82-mqtt-broker-installieren-mosquitto)
    - [8.3 HTTP-Sonos-API installieren](#83-http-sonos-api-installieren)
    - [8.4 Python-Skript einrichten](#84-python-skript-einrichten)
  - [9. Sicherheitsüberlegungen](#9-sicherheitsüberlegungen)
  - [10. Fazit](#10-fazit)

---

## 1. Einleitung
Das Projekt **SonosKids** ist eine DIY-Alternative zur TonieBox, die es ermöglicht, Spotify-Playlists, Hörspiele, Alben oder einzelne Lieder über Sonos-Lautsprecher mittels RFID-Tags abzuspielen. Hierbei werden RFID-Tags verwendet, die von einem ESP8266 mit RFID-Reader ausgelesen und per MQTT an einen zentralen Server übermittelt werden. Der Server verarbeitet die RFID-Tags, kommuniziert mit der Sonos-API und spielt die zugehörigen Inhalte ab.

---

## 2. Projektbeschreibung
Das System besteht aus drei Hauptkomponenten:
1. **RFID-Reader mit ESP8266**: Liest RFID-Tags und sendet die Informationen an einen MQTT-Broker.
2. **Server (Raspberry Pi oder ähnlich)**: Verarbeitet die Tag-Informationen, kommuniziert mit der Datenbank und der Sonos-HTTP-API.
3. **Sonos-Lautsprecher**: Spielt die Inhalte ab, die aus Spotify-Links, Hörspielen oder Radiosendern bestehen.

---

## 3. Systemarchitektur
```
+--------------------+          +-------------------+          +------------------+
| ESP8266 + RFID     |  --->    | MQTT-Broker       |  --->    | Server           |
| (Tag-Leser)        |          | (z.B. Mosquitto)  |          | (Verarbeitung)   |
+--------------------+          +-------------------+          | + HTTP-API       |
                                                               | + MariaDB        |
                                                               | + Node.js/npm    |
                                                               +------------------+
                                                                          |
                                                                      +----+
                                                                      |Sonos|
                                                                      +----+
```

---

## 4. Datenbankstruktur
Die MySQL/MariaDB-Datenbank speichert Informationen zu RFID-Tags und deren zugehörigen Aktionen.

### Tabellenstruktur
**tbl_typ**: Speichert die verfügbaren Medientypen.
```sql
CREATE TABLE tbl_typ (
  typPK INT PRIMARY KEY,
  bezeichnung VARCHAR(200)
);
```

**tbl_karte**: Speichert die RFID-Tags und die zugehörigen Inhalte.
```sql
CREATE TABLE tbl_karte (
  kartePK VARCHAR(8) PRIMARY KEY,
  typFK INT,
  interpret VARCHAR(200),
  titel VARCHAR(255),
  linksuffix VARCHAR(255),
  FOREIGN KEY (typFK) REFERENCES tbl_typ(typPK)
);
```

### Beispieldaten
```sql
INSERT INTO tbl_typ VALUES
(1, 'Hörspiel'),
(2, 'Album'),
(3, 'Playlist'),
(4, 'Radio');

INSERT INTO tbl_karte (kartePK, typFK, interpret, titel, linksuffix) VALUES
('AABBCCDD', 1, 'Die Drei Fragezeichen', 'Folge 001: und der Super-Papagei', 'spotify/now/spotify:track:4N9tvSjWfZXx3eHKblYEWQ');
```

---

## 5. Software-Komponenten
Für die Funktion des Systems sind folgende Software-Komponenten erforderlich:

1. **MariaDB**: Datenbankserver zur Speicherung der RFID-Daten.
2. **MQTT-Broker (z.B. Mosquitto)**: Kommunikation zwischen ESP8266 und Server.
3. **Node.js & npm**: Laufzeitumgebung für die HTTP-Sonos-API.
4. **HTTP-Sonos-API**: Open-Source API zur Steuerung von Sonos-Lautsprechern.
   - Installation via npm: `npm install -g http-sonos-api`
   - Start der API: `http-sonos-api --port 5005`
5. **Python 3**: Zur Verarbeitung der Nachrichten und Kommunikation mit der API und Datenbank.
   - Bibliotheken: `mysql-connector-python`, `paho-mqtt`, `requests`

---

## 6. Ablaufbeschreibung
1. **RFID-Tag erkennen**: Der ESP8266 liest die RFID-Tag-ID und sendet sie per MQTT an den Server.
2. **Datenbankabfrage**: Der Server prüft die ID in der MariaDB-Datenbank.
3. **Medieninhalt abspielen**:
   - Wenn die ID gefunden wird:
     - Der Inhalt (z.B. Spotify-Link) wird über die HTTP-Sonos-API abgespielt.
     - Interpret, Titel und Statusmeldungen werden per MQTT zurückgemeldet.
   - Wenn die ID unbekannt ist:
     - Ein neuer Eintrag wird in der Datenbank erstellt.
     - Statusmeldung "Neue Karte hinzugefügt" wird gesendet.

---

## 7. Hardware-Anforderungen
- **ESP8266**-Modul (z.B. NodeMCU oder Wemos D1 Mini)
- **RFID-Reader** (z.B. MFRC522 oder PN532)
- **RFID-Tags**
- **Raspberry Pi** (oder vergleichbarer Server für MariaDB, MQTT und die HTTP-Sonos-API)
- **Sonos-Lautsprecher**

---

## 8. Installation und Konfiguration

### 8.1 MariaDB installieren und einrichten
```bash
sudo apt update
sudo apt install mariadb-server
sudo mysql_secure_installation
```
Erstellen der Datenbank und Tabellen:
```sql
CREATE DATABASE db_controller;
USE db_controller;
-- Tabellenstruktur siehe oben
```

### 8.2 MQTT-Broker installieren (Mosquitto)
```bash
sudo apt install mosquitto mosquitto-clients
sudo systemctl enable mosquitto
```

### 8.3 HTTP-Sonos-API installieren
```bash
sudo apt install nodejs npm
sudo npm install -g http-sonos-api
http-sonos-api --port 5005
```

### 8.4 Python-Skript einrichten
1. Installiere die notwendigen Python-Bibliotheken:
   ```bash
   pip install mysql-connector-python paho-mqtt requests
   ```
2. Starte das Skript:
   ```bash
   python sonoskids.py
   ```

---

## 9. Sicherheitsüberlegungen
1. **Datenbank-Zugriff**:
   - Verwende starke Passwörter und beschränkte Nutzerrechte.
2. **MQTT-Broker absichern**:
   - Setze ein Passwort für Mosquitto.
   - Verwende TLS/SSL zur sicheren Kommunikation.
3. **Netzwerksicherheit**:
   - Schütze den Server hinter einer Firewall und beschränke Zugriffe.

---

## 10. Fazit
Das Projekt **SonosKids** bietet eine flexible, kostengünstige und spaßige Möglichkeit, Inhalte über RFID-Tags auf Sonos-Lautsprechern abzuspielen. Mit etwas technischer Erfahrung in Python, MQTT und Datenbanken lässt sich dieses System einfach implementieren und erweitern.

---

**Ende der Dokumentation**
