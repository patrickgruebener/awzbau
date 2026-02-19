# STEC Plugin Update Checklist

## Ziel

Sicheres Update von `stachethemes_event_calendar`, ohne die AWZ Performance-Optimierungen zu verlieren.

## Vor dem Update (Pre-Update)

1. Backup erstellen:
   - Dateisystem (mindestens `wp-content/`)
   - Datenbank-Dump
2. Aktuellen Stand dokumentieren:
   - Aktive Plugin-Version
   - Datum/Uhrzeit
   - Gemessene Basiswerte (`tests/manual/performance-calendar.md`)
3. Pruefen, dass folgende AWZ-Dateien vorhanden sind:
   - `wp-content/mu-plugins/awz-stec-performance.php`
   - `wp-content/mu-plugins/awz-stec-i18n-loader-hotfix.php`
   - `wp-content/themes/vantage-childtheme/functions.php`
   - `wp-content/themes/vantage-childtheme/inc/stec-i18n-fallback.php`
4. Kurztest vor Update:
   - `/weiterbildung` laedt
   - Event-Detailseite laedt
   - Buchung bis Checkout funktioniert

## Update-Durchfuehrung

1. STEC Plugin normal aktualisieren (keine manuellen Core-Edits).
2. Cache leeren (falls Server-/Plugin-Cache vorhanden).
3. Keine Aenderung an MU-Plugin/Child-Theme waehrend des Updates.

## Nach dem Update (Post-Update)

### A) Technische Checks

1. Browser-Konsole: keine neuen JS-Fehler auf `/weiterbildung`.
2. REST-Endpoint pruefen:
   - `GET /wp-json/stec/v5/events?...`
   - Header `X-AWZ-STEC-Cache` ist vorhanden (`MISS`, danach `HIT`).
3. Pagination-Header vorhanden:
   - `X-WP-Total`
   - `X-WP-TotalPages`
4. Network-Check auf STEC-Seiten:
   - keine langsamen `stec-de_DE-*.json` 404 Ketten.

### B) Funktionale Checks

1. Kalender zeigt Events korrekt.
2. Monatswechsel/Navigation funktioniert.
3. Event-Detailseite funktioniert.
4. Buchungsflow (`Buchen` -> `Checkout`) funktioniert.
5. Uebersetzungen bleiben korrekt (`Buchen`, `Lehrgangsbuchung`, Checkout-Texte).

### C) Performance Checks

1. Messmatrix aus `tests/manual/performance-calendar.md` erneut ausfuehren.
2. Vorher/Nachher dokumentieren:
   - p50/p95 TTFB
   - p95 Payload
   - Subjektive Ladezeit Kalender

## Regression-Signale (sofort handeln)

1. Kalender leer oder stark verzoegert.
2. REST-Calls >2s dauerhaft.
3. Fehlerhafte Pagination oder Event-Navigation.
4. Falsche Texte oder fehlender `Buchen` CTA.
5. Viele `stec-de_DE-*.json` 404 Requests im Network (i18n-loader Problem).

## Notfallpfad

1. MU-Plugin temporaer deaktivieren:
   - `wp-content/mu-plugins/awz-stec-performance.php` umbenennen.
2. Bei i18n-Loader-Problemen testweise deaktivieren:
   - `wp-content/mu-plugins/awz-stec-i18n-loader-hotfix.php` umbenennen.
3. Erneut testen, ob Problem im Update oder in Overrides liegt.
4. Bei Bedarf komplettes Rollback mit Backup.

## Akzeptanzkriterien

1. Keine funktionalen Regressionen.
2. Performance mindestens auf Vor-Update-Niveau (besser oder gleich).
3. Alle AWZ-Overrides bleiben ohne Plugin-Core-Aenderung aktiv.
