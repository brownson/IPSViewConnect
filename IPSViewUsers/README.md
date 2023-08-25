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

- IP-Symcon ab Version 6.3

### 3. Software-Installation

* Über den Module Store das Modul IPSViewConnect installieren.

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" kann das 'IPSViewUsers'-Modul mithilfe des Schnellfilters gefunden werden.
    - Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/de/service/dokumentation/grundlagen/instanzen/)

__Konfigurationsseite__:

Name                          | Beschreibung
----------------------------- | ---------------------------------
Liste mit Gruppen             | Verwaltung der Gruppen
Liste mit Benutzern           | Verwaltung der Benutzer


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Es werden keine Statusvariablen angelegt

##### Profile:

Es werden keine zusätzlichen Profile hinzugefügt

### 6. PHP-Befehlsreferenz

Name                            | Beschreibung
------------------------------- | ---------------------------------
IVU_AddGroup                    | Hinzufügen einer neuen Gruppe von Benutzern
IVU_ChangeGroup                 | Ändern einer bestehenden Gruppe
IVU_DeleteGroup                 | Löschen einer Gruppe

IVU_AddUser                     | Hinzufügen eines neuen Benutzers
IVU_GetUserExists               | Prüft ob ein Benutzer bereits existiert
IVU_SetUserGroup                | Setzen einer Gruppe eines Benutzers
IVU_SetUserPwd                  | Setzen des Kennworts eines Benutzers
IVU_SetUserView                 | Setzen der View eines Benutzers
IVU_GetUserPwd                  | Liefert das Passwort des übergebenen Benutzers
IVU_GetUserView                 | Liefert die finale View eines Benutzers
IVU_GetUserViewContent          | Liefert die finale View eines Benutzers als Media Content
IVU_GetUserViewID               | Liefert die ViewID des übergebenen Benutzers




