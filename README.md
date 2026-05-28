# IPS_Informations

IP-Symcon Modul zum Auslesen zentraler Systeminformationen.

Dieses Repository ist eine modernisierte Neuimplementierung des alten Moduls `IPSInformations` auf Basis von `IPSModuleStrict`.

## Funktionsumfang

- Anzahl von Events, Instanzen, Kategorien, Links, Medien, Modulen, Objekten, Profilen, Skripten und Variablen
- Anzahl der Module nach Typ
- Lizenz-Benutzername, Lizenz-Typ und Variablen-Limit
- Installierte IP-Symcon Version inklusive Build
- Speicherverbrauch von Script-, Log- und Datenbankverzeichnis
- Archivstatistik: geloggte Variablen und geloggte Werte
- Letzter IP-Symcon Startzeitpunkt
- Optional: WAN IPv4-Adresse
- Automatische Ermittlung des Subscription-Ablaufdatums

## Systemanforderungen

- IP-Symcon ab Version 9.0

## Versionierung

`library.json` wird ueber `.github/scripts/update-library-metadata.php` aktualisiert. Der GitHub Workflow `.github/workflows/update-library-metadata.yml` setzt `build` automatisch auf die Git-Commit-Anzahl und aktualisiert `date`.

Die Version kann bei Bedarf lokal gesetzt werden:

```bash
php .github/scripts/update-library-metadata.php --version=2.1 --next-build
```

## Installation

In der Kern-Instanz `Module Control` dieses Repository hinzufügen:

```text
https://github.com/Burki24/IPS_Informations.git
```

Die Instanz befindet sich anschließend unter den Kern-Instanzen.

## Befehlsreferenz

Aktualisiert alle Werte:

```php
IPSInfo_Update($InstanzID);
```

Ermittelt die WAN IPv4-Adresse:

```php
$result = IPSInfo_GetWANIPv4($InstanzID);
```

Kompatibilitätsfunktion für alte Skripte:

```php
$result = IPSInfo_Get_WAN_IPv4($InstanzID);
```
