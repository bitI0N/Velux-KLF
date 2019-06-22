# IPSWebSockets

Implementierung des Websocket Protokoll in IPS.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang) 
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Hinweise zur Verwendung](#4-hinweise-zur-verwendung)
5. [Einrichten eines Websocket-Client in IPS](#5-einrichten-eines-websocket-client-in-ips)
6. [Einrichten eines Websocket-Server in IPS](#6-einrichten-eines-websocket-server-in-ips)
7. [Einrichten eines Client-Splitter in IPS](#7-einrichten-eines-client-splitter-in-ips)
8. [PHP-Befehlsreferenz](#8-php-befehlsreferenz) 
9. [Parameter / Modul-Infos](#9-parameter--modul-infos) 
10. [Datenaustausch](#10-datenaustausch)
11. [Anhang](#11-anhang)
12. [Lizenz](#12-lizenz)

## 1. Funktionsumfang

  Diese Library stellt verschiedene Module zur Nutzung von WebSockets in IPS bereit.  
  Enthalten ist sowohl ein WebSocket-Client, als auch ein WebSocket-Server.  
  Des weiteren ist ein ClientSplitter enthalten, welcher auf Basis der IP-Adresse der Clients, die Datenströme sauber sortiert an andere Instanzen weiterleiten kann.  

## 2. Voraussetzungen

 - IPS ab Version 4.1, empfohlen IPS 4.2 (Nicht mit 4.0 verwendbar !)
 
## 3. Installation

   Über das Modul-Control folgende URL hinzufügen.  
   `git://github.com/Nall-chan/IPSWebsocket.git`  

   **Bei kommerzieller Nutzung (z.B. als Errichter oder Integrator) wenden Sie sich bitte an den Autor.**  

   Weitere in diesem Modul enthaltene Librarys:

  **PHP-TLS**  
  https://github.com/rnaga/PHP-TLS  
    Copyright (c) 2016 Ryohei Nagatsuka    

  **Pure PHP Elliptic Curve Cryptography Library**  
  https://github.com/phpecc/phpecc  

  **Assert**  
  https://github.com/beberlei/assert  
    Copyright (c) 2011-2013, Benjamin Eberlei, All rights reserved.  

  **AES GCM (Galois Counter Mode) PHP Implementation**  
  https://github.com/Spomky-Labs/php-aes-gcm  
    Copyright (c) 2016 Spomky-Labs  

## 4. Hinweise zur Verwendung

   Die bereitgestellten Module unterstützen direkt keine Hard-/Software welche WebSocket nutzen.  
   Sie dienen nur dazu das Protokoll in IPS einzubinden.  
   Es sind somit andere Module oder Scripte (mit Register-Variable) notwendig um diese Dienste in IPS abzubilden.  
   Der Client und der Server unterstützen die unverschlüsselte (ws://) als auch die verschlüsselte (wss://) Übertragung mit TLS 1.1 & 1.2.  
   Beide unterstützen ebenfalls die Basic-Authentifizierung. Die Anmeldendaten können ohne aktiver Verschlüsselung jedoch mitgelesen werden und sind somit nicht 'sicher'.  
   Bei aktiver Verschlüsselung werden die Zugangsdaten der Basis-Authentifizierung ebenfalls verschlüsselt übertragen.  

### WebSocket-Client:  
  ![](docs/daWebSocketClient.png)  
   Wird der Client vom Server getrennt, so wird die Instanz als Fehlerhaft makiert. Des weiteren versucht IPS dann alle 60 Sekunden die Verbindung wieder aufzubauen.  
   Der Client stellt ein Interface für die RegisterVariable sowie andere IPS-Instanzen welche ein serielles Protokoll nutzen bereit.  
  ![](docs/phyWSC2.png)  
   Ebenso wird ein eigenes Interface für den Datenaustausch bereitgestellt, welches alle Möglichkeiten des Protokolls der untergeordneten Instanz zur Verfügung stellt.
  ![](docs/phyWSC.png)  
  
### WebSocket-Server:  
   Beim anlegen erzeugt die Instanz einmalig eigene selbst-signierte Zertifikate, welche jederzeit durch eigene ersetzt werden können.  
   Der Server ist kein vollwertiger HTTP-Server und unterstützt nur den HTTP-Header für den Verbindungsaufbau der WebSocket-Verbindung.  
   Es wird ein Interface welches dem ServerSocket entspricht bereitgestellt.  
  ![](docs/daWebSocketServer1.png)  
  ![](docs/phyWSS2.png)  
   Ebenso wird ein eigenes Interface für den Datenaustausch bereitgestellt, welches alle Möglichkeiten des Protokolls der untergeordneten Instanz zur Verfügung stellt.
  ![](docs/daWebSocketServer2.png)  
  ![](docs/phyWSS.png)  

### Client Splitter:  
   Der Client Splitter kann sowohl hinter einem IPS-ServerSocket als auch hinter den WebSocketServer betrieben werden und trennt Datenströme nach den IP-Adressen der Clients auf.  
   Der Client Splitter stellt ein Interface für die RegisterVariable sowie andere IPS-Instanzen welche ein serielles Protokoll nutzen bereit.     
  ![](docs/daClientSplitter.png)  
  ![](docs/phyCS.png)  

## 5. Einrichten eines Websocket-Client in IPS

  Unter Instanz hinzufügen '(Splitter)' wählen und ein 'Websocket Client' hinzufügen (Haken bei Alle Module anzeigen!).  
  Es wird automatisch ein 'Client Socket' als übergeordnete Instanz angelegt und fertig konfiguriert.  

   ![](docs/Client.png)  
  In den Einstellungen ist mindestens die URL des entfernten WebSocket-Server einzutragen.  
  Dabei gilt das übliche Schema einer URL aus Protokoll, Host (u.U. Port) und Pfad.  
  z.B. `wss://localhost:9090/meinWebSocketServer/`  

  Die Erweiterten Einstellungen sind abhängig vom zu verbindenen Dienst, brauchen in der Regel aber nicht geändert werden.  

## 6. Einrichten eines Websocket-Server in IPS

  Unter Instanz hinzufügen (Splitter) wählen und ein 'Websocket Server' hinzufügen (Haken bei Alle Module anzeigen!).
  Es wird automatisch ein 'Server Socket' als übergeordnete Instanz angelegt.  

   ![](docs/Server.png)  
   ![](docs/Server41.png)  
  In den Einstellungen ist mindestens ein freier Port (TCP) einzutragen.  
  Die URI bezeichnet den Pfad unter welchen der Server WebSocket Verbindungen annehmen soll.  
  z.B. `/meinWebSocketServer/`  
  Aktuell ist es nicht möglich mehrere WebSocket-Server parallel auf einem Port zu betreiben.  
  Der Modus legt fest ob sich Clients mit oder ohne Verschlüsselung verbinden dürfen.  
  Das optionale Zertifikat wird bei aktiver Verschlüsselung einmalig generiert und ist selbst signiert.  Es kann jederzeit durch ein eigenes Ersetzt werden.  
  Das Zertifikat sowie der private Schlüssel sind bei IPS 4.1 im IPS-Verzeichniss zu finden.  
  z.B.:  

    C:\Programme\IP-Symcon\cert\<ID der Instanz>.cer  
    C:\Programme\IP-Symcon\cert\<ID der Instanz>.key  
    /var/lib/symcon/cert/<ID der Instanz>.cer  
    /var/lib/symcon/cert/<ID der Instanz>.key  

  Ab IPS 4.2 sind diese Dateien ohne Funktion bzw. werden nicht angelegt. Hier sind die Zertifikate innerhalb der IPS-Setting gespeichert und können direkt über die Konfiguration der Instanz hochgeladen werden.  
  Die optionale HTTP Basis-Authentifizierung kann aktiviert, und mit einem Benutzer und Passwort versehen werden.  

## 7. Einrichten eines Client-Splitter in IPS

  Unter Instanz hinzufügen (Splitter) wählen und ein 'Client Splitter' hinzufügen (Haken bei Alle Module anzeigen!).  
  Der Splitter erwartet in der Konfiguration eine IPv4 Adresse und leitet Daten nur dann an die untergeordneten Instanzen weiter, wenn die IP-Adresse übereinstimmt.  
  Der trennt also die Datenströme von verschiedenen Clients, welche sich auf den ServerSocket oder den WebSocket verbinden, auf und ermöglicht es die Daten sauber pro Client zu verarbeiten.   
  ![](docs/ClientSplitter.png)  


## 8. PHP-Befehlsreferenz

### WebSocket-Client:  

```php
bool WSC_SendText(integer $InstanzeID, string $Text);
```
 Sendet die in `$Text` übergeben Daten an den WebSocket-Server, dabei wird als Frame (binary / text) die Einstellung auf der Instanz genutzt.  
 Dieser WebSocket-Frame wird immer mit dem Fin-Flag gesendet.  
 Der Rückgabewert ist `True`, sofern der Client verbunden ist.  

```php
bool WSC_SendPacket(integer $InstanzeID, bool $Fin, int $OPCode, string $Text);
```
 Sendet die in `$Text` übergeben Daten an den WebSocket-Server, dabei wird als Frame der übergeben `$OPCode` genutzt.  
 Der WebSocket-Frame wird nur mit dem Fin-Flag gesendet, wenn `$Fin` = `true` ist.  
 Die möglichen OPCodes sind:  

    0x0 (int 0) für  'continuation' => Bedeutet das ein vorheriges Paket fortgesetzt wird und nicht abgeschlossen ist.  
    0x1 (int 1) für  'text' => Ein neuer Frame dessen Inhalt text ist.  
    0x2 (int 2) für  'binary' => Ein neuer Frame dessen Inhalt binär ist.  

 Der Rückgabewert ist `True`, sofern der Client verbunden ist.  

```php
bool WSC_SendPing(integer $InstanzeID, string $Text);
```
 Senden die in `$Text` übergeben Daten als Payload des Ping den WebSocket-Server.  
 Der Rückgabewert ist `True`, wenn der Server den Ping beantwortet.  

### WebSocket-Server:  

```php
bool WSS_SendPing(integer $InstanzeID, string $ClientIP, string $Text);
```
 Senden die in `$Text` übergeben Daten als Payload eines Ping an die `$ClientIP` eines WebSocket-Clients.
 Der Rückgabewert ist `True`, wenn der Client verbunden ist und den Ping beantwortet.  

### Client-Splitter:  

 (Keine PHP Funktionen)

## 9. Parameter / Modul-Infos

GUIDs der Instanzen (z.B. wenn Instanz per PHP angelegt werden soll):  

| Instanz          | GUID                                   |
| :--------------: | :------------------------------------: |
| Websocket Client | {3AB77A94-3467-4E66-8A73-840B4AD89582} |
| Websocket Server | {7869923C-6E1D-4E66-A0BD-627FAD1679C2} |
| Client Splitter  | {7A107D38-75ED-47CB-83F9-F41228CAEEFA} |

Eigenschaften des 'Websocket Client' für Get/SetProperty-Befehle:  

| Eigenschaft  | Typ     | Standardwert | Funktion                                                                              |
| :----------: | :-----: | :----------: | :-----------------------------------------------------------------------------------: |
| Open         | boolean | false        | false für inaktiv, true für aktiv                                                     |
| URL          | string  |              | Die URL auf die sich verbunden wird                                                   |
| Version      | integer | 13           | Die WebSocket-Version 13, 8 oder 6                                                    | 
| Origin       | string  |              | Das Origin Feld im Protokoll                                                          |
| PingInterval | integer | 0            | In Sekunden, wann ein Ping an den Server gesendet wird                                |
| PingPayload  | string  |              | Die im Ping zu versendenen Daten                                                      |
| Frame        | integer | 1            | Format in welchen Daten versendet werden, wenn der Typ nicht bekannt ist (2 = binär)  |
| BasisAuth    | boolean | false        | true = Basis-Authentifizierung verwenden                                              |
| Username     | string  |              | Benutzername für die Authentifizierung                                                |
| Password     | string  |              | Passwort für die Authentifizierung                                                    |


Eigenschaften des 'Websocket Server' für Get/SetProperty-Befehle:  

| Eigenschaft  | Typ     | Standardwert | Funktion                                                                      |
| :----------: | :-----: | :----------: | :---------------------------------------------------------------------------: |
| Open         | boolean | false        | false für inaktiv, true für aktiv                                             |
| Port         | integer | 8080         | Port auf welchen der Server Verbindungen annimmt                              |
| URI          | string  | /            | URI auf welche Clients sich verbinden dürfen                                  |
| Interval     | integer | 0            | Timeout & Ping-Intervall wenn Clients keine Daten übertragen                  |
| TLS          | boolean | false        | True wenn Transport-Socket-Layer Verbindungen erlaubt sind                    |
| Plain        | boolean | true         | True wenn unverschlüsselte Verbindungen erlaubt sind                          |
| CertFile     | string  | siehe *      | Pfad zum Zertifikat (IPS4.1) / Zertifikat base64 encodiert (IPS4.2>)          |
| KeyFile      | string  | siehe *      | Pfad zum privaten Schlüssel (IPS4.1) / Schlüssel base64 encodiert (IPS4.2>)   |
| KeyPassword  | string  | siehe *      | Passwort vom privaten Schlüssel                                               |
| BasisAuth    | boolean | false        | true = Basis-Authentifizierung verwenden                                      |
| Username     | string  |              | Benutzername für die Authentifizierung                                        |
| Password     | string  |              | Passwort für die Authentifizierung                                            |

\* Sobald TLS auf True gesetzt wird und kein Zertifikat vorliegt, wird ein selbst-signiertes Zertifikat erzeugt und in der Instanz eingetragen.  
  Es ist jederzeit möglich das Zertifikat durch ein eigenes zu erstzen.  
  Ab IPS4.2 können das Zertifikat und der Schlüssel über die Console direkt hochgeladen werden.  

Eigenschaften des 'Client Splitter' für Get/SetProperty-Befehle:  

| Eigenschaft   | Typ     | Standardwert | Funktion                  |
| :-----------: | :-----: | :----------: | :-----------------------: |
| ClientIP      | string  |              | Die IP-Adresse des Client |


## 10. Datenaustausch

### WebSocket-Client:

**Datenempfang:**  
  Vom WebSocket-Client zur untergeordneten Instanz (ReceiveData im fremden Modul).  
  Die Datensätze werden erst nach dem Empfang eines Fin als ein Block weitergeleitet.  
  Der WebSocket-Client buffert die Daten eigenständig bis zum nächten Paket mit gesetzten Fin-Flag.  
  Ping/Pong Funktionalität sowie das manuelle schließen der Verbindung sind aktuell nicht vorgesehen.  

| Parameter    | Typ     | Beschreibung                                              |
| :----------: | :-----: | :-------------------------------------------------------: |
| DataID       | string  | {C51A4B94-8195-4673-B78D-04D91D52D2DD}                    |
| FrameTyp     | integer | 1 = text, 2 = binär                                       |
| Buffer       | string  | Payload                                                   |

  ![](docs/IfWSC.png)  

**Datenversand:**  
  Von der untergeordneten Instanz zum WebSocket-Client (SendDataToParent im fremden Modul).  
  Es ist empfohlen nur den FrameTyp 1 & 2 in Verbindung mit Fin = true zu nutzen!  
  Die Instanz meldet True zurück, solange sie Verbunden ist.  
  
| Parameter    | Typ     | Beschreibung                                              |
| :----------: | :-----: | :-------------------------------------------------------: |
| DataID       | string  | {BC49DE11-24CA-484D-85AE-9B6F24D89321}                    |
| FrameTyp     | integer | 0 = continuation, 1 = text, 2 = binär                     |
| Fin          | bool    | true wenn Paket komplett, false wenn weitere Daten folgen | 
| Buffer       | string  | Payload                                                   |

  ![](docs/IfWSC2.png)  

### WebSocket-Server:

**Datenempfang:**  
  Vom WebSocket-Server zur untergeordneten Instanz (ReceiveData im fremden Modul).  
  Die Datensätze werden erst nach dem Empfang eines Fin als ein Block weitergeleitet.  
  Der WebSocket-Server buffert die Daten eigenständig bis zum nächten Paket mit gesetzten Fin-Flag.  
  Ping/Pong Funktionalität sowie das manuelle schließen der Verbindung sind aktuell nicht vorgesehen.  

| Parameter    | Typ     | Beschreibung                                              |
| :----------: | :-----: | :-------------------------------------------------------: |
| DataID       | string  | {8F1F6C32-B1AD-4B7F-8DFB-1244A96FCACF}                    |
| ClientIP     | string  | Die IP-Adresse des Client von welchem die Daten kommen    |
| ClientPort   | string  | Die Port des Client von welchem die Daten kommen          |
| FrameTyp     | integer | 1 = text, 2 = binär                                       |
| Buffer       | string  | Payload                                                   |

  ![](docs/IfWSS.png)  

**Datenversand:**  
  Von der untergeordneten Instanz zum WebSocket-Server (SendDataToParent im fremden Modul).  
  Es wird true zurückgeliefert wenn die Funktion gemäß FrameTyp erfolgreich ausgeführt wurde.  

| Parameter    | Typ     | Beschreibung                                                    |
| :----------: | :-----: | :-------------------------------------------------------------: |
| DataID       | string  | {714B71FB-3D11-41D1-AFAC-E06F1E983E09}                          |
| ClientIP     | string  | Die IP-Adresse des Client zu welchem die Daten versendet werden |
| FrameTyp     | integer | 0 = continuation, 1 = text, 2 = binär, 8 = close, 9 = ping      |
| Fin          | bool    | true wenn Paket komplett, false wenn weitere Daten folgen       | 
| Buffer       | string  | Payload                                                         |

  ![](docs/IfWSS2.png)  

## 11. Anhang

**Changlog:**  

Version 1.0:  
 - Erstes offizielles Release

## 12. Lizenz

  IPS-Modul:  
  [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  

  Librarys:  
  **PHP-TLS**  
  https://github.com/rnaga/PHP-TLS  
    Copyright (c) 2016 Ryohei Nagatsuka    

  **Pure PHP Elliptic Curve Cryptography Library**  
  https://github.com/phpecc/phpecc  

  **Assert**  
  https://github.com/beberlei/assert  
    Copyright (c) 2011-2013, Benjamin Eberlei, All rights reserved.  

  **AES GCM (Galois Counter Mode) PHP Implementation**  
  https://github.com/Spomky-Labs/php-aes-gcm  
    Copyright (c) 2016 Spomky-Labs  
