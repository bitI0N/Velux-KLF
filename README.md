[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Modul%20Version-0.50-blue.svg?style=flat-square)]()
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)  
[![Version](https://img.shields.io/badge/Symcon%20Version-5.2%20%3E-green.svg?style=flat-square)](https://www.symcon.de/forum/threads/41251-IP-Symcon-5-2-%28Testing%29)
[![StyleCI](https://styleci.io/repos/193268520/shield?style=flat-square)](https://styleci.io/repos/193268520)  

# VeluxKLF200

Diese Implementierung der API von dem Velux KLF200 Gateway
ermöglich die Einbindung von allen io-homecontrol® Geräten, welche von diesem Gateway unterstützt werden.  


## Dokumentation

**Inhaltsverzeichnis**


1. [Funktionsumfang](#1-funktionsumfang) 
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation) 
4. [Einrichten der Instanzen in IP-Symcon](#5-einrichten-der-instanzen-in-ip-symcon)
5. [Anhang](#5-anhang)  
    1. [GUID der Module](#1-guid-der-module)
    2. [Changlog](#2-changlog)
    3. [Spenden](#3-spenden)
6. [Lizenz](#6-lizenz)

## 1. Funktionsumfang

### [KLF200 Configurator:](KLF200Configurator/)  
### [KLF200 Gateway:](KLF200Gateway/)  
### [KLF200 Node:](KLF200Node/)  

## 2. Voraussetzungen

 - IPS 5.2 (testing Release) oder neuer  
 - KLF200 io-homecontrol® Gateway  
    - KLF muss per LAN angeschlossen sein  
    - KLF Firmware 2.0.0.71 oder neuer   

## 3. Software-Installation

**IPS 5.3:**  
   Bei privater Nutzung:
     Über den 'Module-Store' in IPS.  
   **Bei kommerzieller Nutzung (z.B. als Errichter oder Integrator) wenden Sie sich bitte an den Autor.**  

## 4. Einrichten der Instanzen in IP-Symcon

Ist direkt in der Dokumentation der jeweiligen Module beschrieben.  
Es wird empfohlen die Einrichtung mit der Konfigurator-Instanz zu starten ([KLF200 Configurator:](KLF200Configurator/)).  

## 5. Anhang

###  1. GUID der Module
 
 
| Modul               | Typ          |Prefix  | GUID                                   |
| :-----------------: | :----------: | :----: | :------------------------------------: |
| KLF200 Configurator | Configurator | KLF200 | {38724E6E-8202-4D37-9FA7-BDD2EDA79520} |
| KLF200 Gateway      | Splitter     | KLF200 | {725D4DF6-C8FC-463C-823A-D3481A3D7003} |
| KLF200 Node         | Device       | KLF200 | {4EBD07B1-2962-4531-AC5F-7944789A9CE5} |

### 2. Changlog

**Changlog:**

 Version 0.7:  
 - ReceiveFilter waren defekt.  Dadurch konnten bestimmte Nodes nicht erzeugt oder verwendet werden.  
 
 Version 0.6:
 - Button im Splitter für das Lesen der Firmwareversion war ohne Funktion.  
 - Fehler im Konfigurator, wenn Namen der Geräte einen Umlaut enthielt.  
 - Der Konfigurator hat eine neuen Button, um im laufenden Betrieb die Geräteliste neu zu laden.  

 Version 0.5:  
 - Öffentliche Betaversion  
 - Dokumentation erstellt  

 Version 0.1:  
 - Testversion  


### 3. Spenden  
  
  Die Library ist für die nicht kommzerielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=G2SLW2MEMQZH2" target="_blank"><img src="https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donate_LG.gif" border="0" /></a>

## 6. Lizenz

### IPS-Modul:  
  [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  

### Submodules:  
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
