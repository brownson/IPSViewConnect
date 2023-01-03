# IPSViewUsers Modul for IP-Symcon

Das Modul ermöglicht die Zuordnung von Benutzern zu Views mit eigenem Kennwort

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [PHP-Befehlsreferenz](#6-php-befehlsreferenz)

### 1. Funktionsumfang

* IPSViewUsers 

### 2. Voraussetzungen

- IP-Symcon ab Version 5.4
- IPSStudio ab der Version 6.0

### 3. Software-Installation

* Über den Module Store das Modul IPSViewUsers installieren.
* Alternativ über das Module Control folgende URL hinzufügen:
`https://github.com/brownson/IPSViewUsers`

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" kann das 'IPSMetaManager'-Modul mithilfe des Schnellfilters gefunden werden.
    - Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name                          | Beschreibung
----------------------------- | ---------------------------------
UserList                      | Liste mit Benutzern


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Es werden keine Statusvariablen angelegt

##### Profile:

Es werden keine zusätzlichen Profile hinzugefügt

### 6. PHP-Befehlsreferenz

Name                            | Beschreibung
------------------------------- | ---------------------------------
IVU_UserExists                  | Die Funktion prüft, ob der User mit dem übergebenen Namen existiert
IVU_GetUserPwd                  | Liefert das Passwort des übergebenen Usernames
IVU_GetUserViewID               | Liefert die ViewID des übergebenen Usernames




