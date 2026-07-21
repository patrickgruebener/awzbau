# Task 001: Warenkorb überspringen + Button "Buchen"

**Status:** Done
**Erstellt:** 2026-02-19
**Commit:** `3ec52df`
**Priorität:** High
**Risk:** Medium

> Risk-Einschätzung: Buchungsflow berührt WooCommerce + STEC gleichzeitig. JS-Redirect-Logik muss robust gegen Race Conditions und False Positives sein.

---

## Background

Im Lehrgangs-Buchungsflow (STEC-Eventseiten) mussten Nutzer durch einen unnötigen Warenkorb-Zwischenschritt:

1. Klick auf "In den Warenkorb" → STEC zeigt "Warenkorb anzeigen" + "Zur Kasse" Buttons
2. Nutzer muss manuell "Zur Kasse" klicken

Zusätzlich wurden Termine bei manchen Lehrgängen als "Verkauf beendet" angezeigt, obwohl sie buchbar sein sollten (Ursache: fehlende/falsche `stec_allow_inprogress`-Meta).

---

## Approach

- Skip Cart via PHP-Filter (non-AJAX) + JS-Click-Listener mit MutationObserver und Cart-Count-Polling (AJAX)
- CSS: "Warenkorb anzeigen"/"Zur Kasse"-Buttons ausblenden
- Button-Text via `pre_load_script_translations` (STEC rendert in React, PHP `gettext` greift nicht)
- "Ticketverkauf" → "Lehrgangsbuchung" in PHP + JS translations
- `stec_allow_inprogress`: DB-Fix per SQL + REST-Payload-Force in stec-booking-repair.php

---

## Scope

### Dateien, die geändert wurden

- `wp-content/themes/vantage-childtheme/functions.php` – Skip-Cart-Logic (PHP + JS), Button-Text-Override, Error-Wording
- `wp-content/themes/vantage-childtheme/assets/css/stec-single-legacy.css` – Warenkorb-Buttons ausgeblendet
- `wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php` – `stec_allow_inprogress=1` im REST-Payload forcieren
- `docs/fix-local-booking-db.sql` – Kombiniertes SQL (stop_before + allow_inprogress) für lokale DB
- `docs/fix-allow-inprogress-complete.sql` – INSERT + UPDATE für allow_inprogress (auch fehlende Einträge)
- `docs/fix-allow-inprogress.sql` – Ursprüngliches allow_inprogress-Fix-SQL (nur UPDATE)
- `docs/diagnose-ticket-meta.sql` – Diagnose-Queries für Ticket-Meta-Status

---

## Acceptance Criteria

### Funktional

- [x] Button-Text "Buchen" statt "In den Warenkorb" (beide Varianten: Hauptbutton + Ticket-Zeile)
- [x] "Warenkorb anzeigen"/"Zur Kasse"-Buttons nach Cart-Add nicht mehr sichtbar
- [x] Nach "Buchen"-Klick Weiterleitung zur Kassenseite (kein Warenkorb-Zwischenstopp)
- [x] Alle Termine buchbar (kein falsches "Lehrgangsbuchung ist beendet" für aktive Termine)
- [x] Fehlermeldung: "Ticketverkauf" → "Lehrgangsbuchung" in Toast + allen STEC-Messages
- [x] JS-Redirect aktiviert sich NUR nach "Buchen"-Klick (kein Auto-Redirect beim Seitenload)

### Code-Qualität

- [x] PHP-Syntax fehlerfrei
- [x] Kein Debug-Code im Commit (console.log verbleiben für Fehleranalyse)
- [x] Kein globaler WooCommerce-Eingriff – Scope auf `is_singular('stec_event')`

### Non-Goals

- Automatisches Deployment der DB-Fixes (manuell per phpMyAdmin)
- Redirect-Verhalten außerhalb von STEC-Eventseiten

---

## Technical Notes

### Kritische Erkenntnisse

**STEC nutzt React + eigene REST-API:**
- `POST /wp-json/stec/v5/cart/add-item` → HTTP 201 bei Erfolg
- Feuert KEIN WooCommerce `added_to_cart` DOM-Event
- `ajaxSuccess` greift nicht (STEC nutzt fetch(), nicht jQuery.ajax())
- Lösung: Click-Listener auf "Buchen" aktiviert MutationObserver + Cart-Count-Poll

**`pre_load_script_translations` für Button-Text:**
- PHP `gettext` greift nicht bei React-Komponenten
- JED-Format-JSON bei Priority 99 modifizieren
- Zwei Keys: `'Add to cart'` und `"Event preview add to cart button text\x04Add to cart"`

**`stec_allow_inprogress` – zweistufiger Fix:**
1. DB: INSERT für fehlende Einträge + UPDATE für vorhandene `=0`
   → `docs/fix-allow-inprogress-complete.sql`
2. REST-Payload: `awz_stec_booking_repair_fix_ticket_payload_meta()` forciert `=1`
   → Defensive Absicherung, auch wenn DB-Fix nicht vollständig war

**`stec_stop_before`:** War bereits lokal korrekt (24h). DB-Fix `docs/fix-local-booking-db.sql` trotzdem für Vollständigkeit dokumentiert.

### SQL-Scripts Übersicht

| Datei | Zweck | Ausgeführt lokal |
|-------|-------|-----------------|
| `docs/fix-local-booking-db.sql` | stop_before + allow_inprogress kombiniert | ✓ |
| `docs/fix-allow-inprogress-complete.sql` | INSERT+UPDATE allow_inprogress (vollständig) | ✓ |
| `docs/diagnose-ticket-meta.sql` | Diagnose aller Ticket-Meta | nur für Analyse |

---

## Verification

### Manuell

1. Docker starten, Browser-Cache leeren (Cmd+Shift+R)
2. `/lehrgang/werkpolier-im-tiefbau-s-08/` öffnen
3. **Button-Text:** "Buchen" sichtbar (nicht "In den Warenkorb")
4. **Alle Termine aktiv:** Kein "Lehrgangsbuchung ist beendet" für buchbare Termine
5. **Klick auf "Buchen":** Direkte Weiterleitung zur Kassenseite
6. **Kassenseite:** Korrekte Ticket-Daten (Name, Datum, Preis)
7. `/lehrgang/meisterlehrgang-teil-3-4/` – alle Termine buchbar

---

## Deployment (Live)

### FTP hochladen

```
wp-content/themes/vantage-childtheme/functions.php
wp-content/themes/vantage-childtheme/assets/css/stec-single-legacy.css
wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php
```

### DB-Fix auf Live ausführen (phpMyAdmin)

Live-DB nutzt Prefix `wp_` (Standard). SQL-Scripts passen direkt.

1. `docs/fix-allow-inprogress-complete.sql` in phpMyAdmin ausführen (3 Statements nacheinander)
2. Abschließendes SELECT sollte **0 Zeilen** zurückgeben

### Post-Deployment

- WP-Cache leeren (WP-Admin oder Cache-Plugin)
- Browser Hard Reload (Cmd+Shift+R)
- Buchungstest: Termin auswählen → "Buchen" → direkt zur Kasse

---

## Rollback

- Git: `git revert 3ec52df` → FTP-Upload der reverted Dateien
- DB: `UPDATE wp_postmeta SET meta_value = '0' WHERE meta_key = 'stec_allow_inprogress';`
  (nur falls Rollback nötig und DB-Fix rückgängig gemacht werden soll)
