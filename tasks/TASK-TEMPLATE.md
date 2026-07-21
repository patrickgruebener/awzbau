# Task: [Name]

**Status:** Draft | In Progress | Done
**Erstellt:** YYYY-MM-DD
**Priorität:** High | Medium | Low
**Risk:** Low | Medium | High

> Risk-Begründung: [z.B. "nur CSS-Änderung, kein PHP", "schreibt Booking-Meta in DB", "berührt Live-Checkout-Flow"]

---

## Background

[Problem und Motivation in 2–4 Sätzen. Warum existiert dieser Task? Welcher Nutzerschmerz oder technische Schuld steckt dahinter?]

---

## Approach

[Kurze Beschreibung des geplanten Lösungswegs – nicht die Implementierung, sondern die Strategie. Z.B. "Filter-Hook statt Core-Patch", "CSS Custom Property Override", "stec_get_product_data-Filter". Agent soll diesen Weg umsetzen, nicht neu erfinden.]

---

## Scope

### Dateien, die geändert werden

- `wp-content/themes/vantage-childtheme/functions.php` – [Grund]
- `wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php` – [Grund, falls betroffen]
- `wp-content/themes/vantage-childtheme/assets/css/stec-single-legacy.css` – [Grund, falls betroffen]

[Nicht relevante Zeilen löschen]

### Dateien, die NICHT geändert werden (off-limits)

- `wp-content/plugins/` – Plugins niemals direkt ändern
- [Weitere Dateien, die explizit geschützt sind]

### Neue Dateien

[Z.B. "Keine neuen Dateien" oder "Neue Datei erlaubt unter `wp-content/themes/vantage-childtheme/inc/`"]

---

## Acceptance Criteria

Jedes Kriterium muss binär prüfbar sein (ja/nein).

### Funktional

- [ ] [Konkretes, beobachtbares Verhalten auf der Website]
- [ ] [Zweites Verhalten, z.B. "Label X zeigt jetzt Y"]
- [ ] Edge Case: [Was passiert im Grenzfall?]

### Code-Qualität

- [ ] PHP-Syntax-Check grün: `docker exec awz-wordpress php -l /var/www/html/wp-content/themes/vantage-childtheme/functions.php`
- [ ] Keine `console.log` oder Debug-Ausgaben committed
- [ ] Bestehende Booking-Funktionen unverändert (TC-01 bis TC-04 in `tests/manual/booking-flow.md` noch grün)

### Non-Goals

- [Was dieser Task bewusst NICHT löst]
- [Welche verwandten Verbesserungen ausgeschlossen sind]

---

## Technical Notes

### Relevante STEC-Hooks

[Nur die für diesen Task relevanten Hooks eintragen]

| Hook | Datei | Zweck |
|------|-------|-------|
| `stec_shortcode_atts` | functions.php:26 | Events öffnen in Einzelseite |
| `stec_get_product_data` | stec-booking-repair.php:526 | REST-Payload bereinigen |
| `stec_before_add_to_cart` | stec-booking-repair.php:383 | Ticket-Meta vor Validierung |
| `pre_load_script_translations` | functions.php:279 | JS-Translations Override |

### Patterns / Konventionen

- **CSS-Overrides:** Immer via `--stec-*` Custom Properties, nie interne STEC-Klassen direkt
- **DB-Optionen vs. Filter:** `stec_settings` in DB hat Vorrang vor `stec_default_settings`-Filter
- **stop_before:** Immer in **Stunden** (nicht Sekunden), Normalisierung via `awz_stec_booking_repair_normalize_stop_before_hours()`
- **WooCommerce-Scope:** Booking-Änderungen auf `is_singular('stec_event')` beschränken

### Constraints

- [Technische Einschränkung, z.B. "Plugin X cacht diese Daten – WP-Cache nach Deployment leeren"]
- [Abhängigkeit, z.B. "Benötigt WooCommerce >= X.Y"]

### Abhängigkeiten zu anderen Tasks

- [Task-Name / Datei] – [Wie hängt es zusammen?]

---

## Verification

### PHP-Syntax-Check (vor Commit)

```bash
docker exec awz-wordpress php -l /var/www/html/wp-content/themes/vantage-childtheme/functions.php
docker exec awz-wordpress php -l /var/www/html/wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php
```

Erwartetes Ergebnis: `No syntax errors detected`

### Manuell (lokal, http://localhost:8080)

1. Docker starten: `docker compose up -d`
2. Browser-Cache leeren (Cmd+Shift+R oder Inkognito)
3. [Konkrete URL aufrufen: `http://localhost:8080/lehrgang/[slug]/`]
4. **Prüfen:** [Was genau sehen? Was klicken?]
5. **Erwartetes Ergebnis:** [Konkret, nicht "es sollte funktionieren"]

### SQL-Diagnose (falls DB betroffen)

```sql
-- [Diagnosefrage als SQL]
SELECT ... FROM wp_postmeta WHERE ...;
-- ERWARTETES ERGEBNIS: [Was soll zurückkommen?]
```

---

## Rollback

[Wie wird dieser Change rückgängig gemacht?]

- **Code:** `git revert [commit-hash]` → FTP-Upload der reverted Dateien
- **DB (falls geändert):** [SQL-Statement zum Zurücksetzen, oder "kein DB-Eingriff"]
- **Backup-Pfad:** `backups/vantage-childtheme-live-backup-YYYY-MM-DD/`

---

## Ask Before Proceeding

Agent soll STOPPEN und fragen, bevor er handelt:

- [ ] Schreibender DB-Zugriff auf Live-Datenbank
- [ ] FTP-Upload (Deployment) ohne vorherige Bestätigung
- [ ] Änderungen außerhalb des definierten Scope (andere Plugins, Parent Theme)
- [ ] [Weitere projektspezifische Eskalationspunkte]

[Falls keine Eskalation nötig: "Keine – Agent kann selbstständig umsetzen."]

---

## Related Docs

- `CLAUDE.md` – Architektur-Übersicht, Dev-Commands
- `docs/deployment.md` – FTP-Deployment-Workflow
- `tests/manual/booking-flow.md` – Buchungsflow-Checkliste
- [Weitere relevante Docs oder Tasks]
