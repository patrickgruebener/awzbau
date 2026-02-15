# STEC Booking Handover (2026-02-15)

## Zweck

Diese Doku beschreibt den kompletten aktuellen Stand der STEC-Buchungsfixes in `vantage-childtheme`, inklusive Historie, Ursachen, Code-Änderungen, lokal ausgeführten DB-Korrekturen und Live-Deployment-Anleitung.

## Executive Summary

- Ziel war die Stabilisierung der Lehrgangs-Buchung (Tickets/Produkte) nach STEC-Migration und Zuordnung per Hilfstool.
- Es gab drei Hauptfehler:
  1. `Entschuldigung, dieses Produkt ist momentan nicht verfügbar`
  2. `Unknown or bad format (PTH)` beim Warenkorb-Add
  3. `Der Verkauf ist beendet` bei eigentlich buchbaren Terminen
- Zusätzlich UI-Anpassung:
  - Doppelte Tabs `Tickets` + `Produkte` konsolidieren zu einem Buchungs-Tab `Termine`
  - Counter in `Einführung` ausblenden
- Aktueller Code-Stand ist committed als `4daf5c2`.

## Historie und Root Cause

## 1) Produkt nicht verfügbar

- Kontext: Nach Ticket-Zuordnung waren Events sichtbar, aber Produkte wurden als nicht verfügbar markiert.
- Ursache: Legacy-Ticketprodukte hatten unvollständige STEC-Meta (u.a. Limits/Flags), die v5-Logik voraussetzt.
- Fix: Defaulte Ticket-Meta wird bei Sync, Payload und Add-to-Cart defensiv ergänzt.

## 2) `Unknown or bad format (PTH)`

- Kontext: Fehler beim Klick auf `In den Warenkorb`.
- Ursache: In STEC Booking-Code wurde `DateInterval('PT' . $stop_before . 'H')` erzeugt; bei leer/invalidem `stec_stop_before` entsteht ungültiges Intervall.
- Fix:
  - Meta vor Add-to-Cart validieren/auffüllen (`stec_before_add_to_cart`).
  - Produkt-Payload vor STEC-Verarbeitung absichern (`stec_get_product_data`).
  - `stec_stop_before` normalisieren.

## 3) `Der Verkauf ist beendet` für alle Termine

- Kontext: Nach vorherigen Fixes wurden viele buchbare Termine trotzdem als beendet angezeigt.
- Ursache: `stec_stop_before` stand bei mehreren Produkten auf `86400` (Sekunden), STEC interpretiert Stunden.
- Lokaler DB-Fix:
  - Werte in Sekunden, die durch 3600 teilbar sind, auf Stunden konvertiert.
  - Ergebnis lokal: `14` Zeilen normalisiert (`86400 -> 24`).
- Wichtig: Diese DB-Korrektur ist datenbankseitig, nicht nur Code.

## Code-Änderungen (committed)

Commit: `4daf5c2`  
Message: `Fix STEC ticket booking flow and simplify single event tabs`

### Geänderte Dateien

1. `wp-content/themes/vantage-childtheme/functions.php`
2. `wp-content/themes/vantage-childtheme/single-stec_event.php`
3. `wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php`
4. `wp-content/themes/vantage-childtheme/inc/stec-i18n-fallback.php`
5. `wp-content/themes/vantage-childtheme/assets/css/stec-single-legacy.css`
6. `wp-content/themes/vantage-childtheme/assets/js/stec-single-tabs.js`

### Relevante Hooks/Funktionen

- `functions.php`
  - `add_filter('gettext', ...)` fuer STEC CTA-Text (`Add to cart` -> `Buchen`)  
    Siehe `wp-content/themes/vantage-childtheme/functions.php:44`
  - Enqueue Legacy-STEC-CSS  
    `wp-content/themes/vantage-childtheme/functions.php:94`
  - Enqueue Tab-Override-JS auf `single stec_event`  
    `wp-content/themes/vantage-childtheme/functions.php:113`

- `inc/stec-i18n-fallback.php`
  - JS-Translations-Fallback via `pre_load_script_translations` fuer Domain `stec`  
    `wp-content/themes/vantage-childtheme/inc/stec-i18n-fallback.php:53`
    `wp-content/themes/vantage-childtheme/inc/stec-i18n-fallback.php:146`

- `inc/stec-booking-repair.php`
  - `awz_stec_booking_repair_normalize_stop_before_hours`  
    `wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php:231`
  - `awz_stec_booking_repair_ensure_ticket_meta_defaults`  
    `wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php:259`
  - Add-to-cart-Absicherung via `stec_before_add_to_cart`  
    `wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php:340`
    `wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php:383`
  - Payload-Absicherung via `stec_get_product_data`  
    `wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php:519`
    `wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php:561`

- `assets/js/stec-single-tabs.js`
  - Tab-Logik fuer Single Event:
    - `Tickets` -> `Termine`
    - `Produkte` ausblenden, wenn `Tickets` vorhanden
    - falls nur `Produkte` existiert: umbenennen zu `Termine`
  - Nutzt Render-Hook `window.stecOnEventTabContentRender` plus `MutationObserver`.

- `assets/css/stec-single-legacy.css`
  - Counter in Einfuehrung ausblenden:
    - `.single-stec_event .stec-single-page .stec-event-counter { display: none !important; }`
  - Safety fuer versteckten Produkte-Tab:
    - `.awz-stec-hidden-products-tab { display: none !important; }`

## Lokale DB-Korrektur (wichtig fuer Live-Replikation)

Diese Korrektur wurde lokal bereits ausgefuehrt, aber nicht automatisch per FTP uebernommen.

### Zweck

- `stec_stop_before` in Sekunden (`86400`) auf Stunden (`24`) normalisieren, damit STEC Verkauf nicht faelschlich als beendet markiert.

### SQL (Prefix anpassen, z.B. `wp_`)

```sql
SELECT meta_id, post_id, meta_value
FROM wp_postmeta
WHERE meta_key = 'stec_stop_before'
  AND meta_value REGEXP '^[0-9]+$'
  AND CAST(meta_value AS UNSIGNED) >= 3600
  AND MOD(CAST(meta_value AS UNSIGNED), 3600) = 0;

UPDATE wp_postmeta
SET meta_value = CAST(CAST(meta_value AS UNSIGNED) / 3600 AS CHAR)
WHERE meta_key = 'stec_stop_before'
  AND meta_value REGEXP '^[0-9]+$'
  AND CAST(meta_value AS UNSIGNED) >= 3600
  AND MOD(CAST(meta_value AS UNSIGNED), 3600) = 0;
```

### Lokal dokumentiertes Ergebnis

- Vorher: `stec_stop_before=86400` bei 14 Ticket-Produkten
- Nachher: 14 Zeilen normalisiert (u.a. Produkt `3184` auf `24`)

## Live Deployment fuer diesen Stand

1. Backup erstellen (Dateien + DB).
2. Genau diese 6 Dateien per FTP hochladen:
   - `/wp-content/themes/vantage-childtheme/functions.php`
   - `/wp-content/themes/vantage-childtheme/single-stec_event.php`
   - `/wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php`
   - `/wp-content/themes/vantage-childtheme/inc/stec-i18n-fallback.php`
   - `/wp-content/themes/vantage-childtheme/assets/css/stec-single-legacy.css`
   - `/wp-content/themes/vantage-childtheme/assets/js/stec-single-tabs.js`
3. Falls noetig Ordner anlegen:
   - `/wp-content/themes/vantage-childtheme/inc/`
   - `/wp-content/themes/vantage-childtheme/assets/css/`
   - `/wp-content/themes/vantage-childtheme/assets/js/`
4. Caches leeren (WP-Cache, Server-Cache, CDN, Browser Hard Reload).
5. DB-Normalisierung fuer `stec_stop_before` auf Live ausfuehren (siehe SQL oben).
6. Buchungstests durchfuehren (siehe naechster Abschnitt).

## Live Test-Checkliste (Buchungen)

1. Lehrgangsseite oeffnen:
   - Nur ein Buchungs-Tab sichtbar (`Termine`)
   - Kein doppelter `Produkte`-Tab
2. Tab `Einfuehrung` pruefen:
   - Counter nicht sichtbar
3. Termin in Warenkorb legen:
   - Kein `Unknown or bad format (PTH)`
   - Kein `Produkt nicht verfuegbar` bei validen Produkten
4. Produkte mit offenen Verkaufszeitraeumen:
   - Kein falsches `Der Verkauf ist beendet`
5. Warenkorb/Checkout bis kurz vor Zahlung testen.

## Offene Punkte / Feinschliff

- Weitere UI-Feinheiten sind noch offen und koennen in Folge-Commit(s) passieren.
- Falls der Buttontext im Frontend weiterhin `In den Warenkorb` bleibt:
  - Das kann aus JS-i18n kommen; dann ueber STEC-Translation bzw. spezifischere Frontend-Override loesen.

## Hinweise fuer den naechsten Agenten

- Ausgangspunkt ist Commit `4daf5c2` auf Branch `main`.
- Zuerst Live-Replikation/Tests abwarten, bevor neue Refactors gemacht werden.
- Bei erneuten Verkaufsstatus-Problemen immer zuerst `stec_stop_before` in der Live-DB verifizieren.
