# Debug-Ausgaben: IPSInfo

Grundlage ist der abgenommene OpenHomeAlarm-Pilot und `SymconDevelopment/standards/DEBUG_OUTPUT.md`.

## Aufbau und Umfang

Die Kategorie `<Modulklasse> | <Ereignis> | <Funktion>:<Zeile>` enthält `START`, `ENDE`, `AKTION`, `SENDEN`, `EMPFANG` oder `FEHLER`. Die Nutzlast besitzt `Instanz`, `Vorgang`, `Aufruf`, `Meldung` und `Bezug`. Jeder äußere Modulaufruf erhält eine neue Vorgangskennung; verschachtelte Aufrufe teilen sie. Die erfassten Funktionen enden auch bei früher Rückgabe und PHP-Ausnahmen über `finally` mit ihrem wirklichen Ergebnis. Reine interne Berechnungen bleiben ohne eigene Start-/Endpaare.

Systemaktualisierung, Kernelbereitschaft, Statuswerte, HTTP-Leseanfragen und Konto-/Subscription-Abfragen. Zugangsdaten, Lizenzbenutzer, WAN-Adresse, URLs, geladene Konto-Seiten und Dateipfade bleiben geschützt. Unveränderte Werte werden weiterhin nicht erneut geschrieben.

## Gemeinsame Quelle

Der zentrale `DebugFlowHelper` nutzt `DebugHelper` für Bereinigung und Begrenzung auf 4096 Bytes. Beide werden über das gezielte Bundle-Verfahren und die Subscription eingebunden; die Quell-Dateien werden im Consumer nicht lokal bearbeitet. Der neue Helper ist noch lokale Entwicklungsarbeit. `source_sha: working-tree` bezeichnet ausdrücklich keinen veröffentlichten Commit. Bestehende Modul-IDs, APIs und Versionsmetadaten bleiben erhalten; ältere deklarierte Symcon-Mindestversionen werden im Debug-Auftrag nicht angehoben.

Debug verursacht keine zusätzlichen Steueraufträge oder Quellwertabfragen. Ein ausgefallener Debug-Kanal wird abgefangen. Die Übergabe an eine Schnittstelle beweist noch keine Wirkung am Gerät oder Client. Text-/Strukturrückgaben werden nur nach Umfang beschrieben, numerische/boolsche Ergebnisse entsprechend ihrem Vertrag neutral ausgegeben.

## Nachweise und verbleibende Abnahme

php tests/run.php prüft Kernel-Sperre, unveränderte/geschriebene Statuswerte, Offline-Leseantworten, Schutz von Lizenzidentitäten und Bodies sowie Kanalfehler. Ein umfassender historischer Gesamttest oder ein lokaler StylePHP-Aufbau existierte in diesem Checkout nicht.

Die OHA-Abnahme wird nicht auf andere Module übertragen. Lesbarkeit, Datenmenge, Laufzeit und reale Schnittstellen-/Gerätewirkung sind jeweils in Symcon praktisch abzunehmen. Es wurden keine Module installiert, keine Live-Systeme geändert und keine Commits oder Veröffentlichungen ausgeführt.
