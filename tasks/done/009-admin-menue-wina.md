# Task: Admin-Menü vereinfachen (Wina & Johanna)

**Status:** Done
**Erstellt:** 2026-03-04
**Priorität:** Low
**Risk:** Low

> Risk-Begründung: Reines Admin-UI-Feature. Kein Einfluss auf Frontend, Buchungsflow oder Datenbank. Nur `remove_menu_page()` und `menu_order`-Filter — Standard-WP-API.

---

## Background

Wina Benner und Johanna Fick (beide AWZ-Mitarbeiterinnen, Admin-Rolle) sehen im WP-Admin das volle Standard-Menü mit vielen Einträgen, die sie nie nutzen. Wina hat konkret folgende Items als täglich genutzt benannt: Medien, Beiträge, Seiten, STEC, Produkte, Bestellungen, LayerSlider, MetaSlider, Design, Werkzeuge. Der Rest ist Rauschen.

---

## Approach

Neues mu-plugin `awz-admin-menu.php` — passt zum bestehenden Muster (3 mu-plugins existieren bereits). Kein Drittanbieter-Plugin nötig. Zwei WordPress-Filter (`custom_menu_order`, `menu_order`) sortieren die Prioritäts-Items nach oben, `remove_menu_page()` blendet den Rest aus.

**Geltungsbereich via Blacklist:** Ein Array `$awz_full_access` definiert Logins, die das volle Menü sehen (`admin` = Patrick). Alle anderen Admins (Wina, Johanna) bekommen automatisch das vereinfachte Menü.

---

## Scope

### Neue Dateien

- `wp-content/mu-plugins/awz-admin-menu.php` — neues mu-plugin

### Dateien, die NICHT geändert werden

- `wp-content/themes/vantage-childtheme/functions.php`
- `wp-content/plugins/` — Plugins niemals direkt ändern
- Alle anderen Dateien

---

## Menü-Konfiguration

### Oben (Prio-Reihenfolge)

| Pos | Slug | Label |
|-----|------|-------|
| 1 | `upload.php` | Medien |
| 2 | `edit.php` | Beiträge |
| 3 | `edit.php?post_type=page` | Seiten |
| 4 | `stec` | STEC |
| 5 | `edit.php?post_type=product` | Produkte |
| 6 | `edit.php?post_type=shop_order` | Bestellungen |
| 7 | `layerslider` | LayerSlider |
| 8 | `metaslider` | MetaSlider |
| 9 | `themes.php` | Design |
| 10 | `tools.php` | Werkzeuge |

### Ausgeblendet

| Slug | Label |
|------|-------|
| `edit-comments.php` | Kommentare |
| `plugins.php` | Plugins |
| `options-general.php` | Einstellungen |
| `users.php` | Benutzer |
| `mailjet_settings_page` | Mailjet |
| `mailjet_form_7` | Mailjet Form 7 |
| `woocommerce` | WooCommerce (Main-Menü, Produkte/Bestellungen bleiben) |

**Hinweis:** `remove_menu_page` versteckt nur den Menüeintrag. Direktzugriff per URL bleibt möglich (da Admin-Rolle erhalten). Gewollt — kein Sicherheitsfeature, nur Aufräumen.

---

## Acceptance Criteria

### Funktional

- [ ] Wina/Johanna sehen Medien, Beiträge, Seiten, STEC, Produkte, Bestellungen, LayerSlider, MetaSlider, Design, Werkzeuge — in dieser Reihenfolge oben
- [ ] Wina/Johanna sehen NICHT: Kommentare, Plugins, Einstellungen, Benutzer, Mailjet, WooCommerce (Settings-Menü)
- [ ] `admin`-Account sieht das komplette, unveränderte Menü
- [ ] Werkzeuge → Lehrgangsreihenfolge weiterhin erreichbar
- [ ] Werkzeuge → AWZ STEC Repair weiterhin erreichbar

### Code-Qualität

- [ ] PHP-Syntax-Check grün für `awz-admin-menu.php`
- [ ] Keine `console.log` oder Debug-Ausgaben

### Non-Goals

- Dieses Feature ändert keine Berechtigungen — es ist rein visuell
- Frontend, Buchungsflow und DB werden nicht berührt
- Keine Rollenänderungen (Wina/Johanna bleiben Administrator)

---

## Technical Notes

### WordPress-Hooks

| Hook | Zweck |
|------|-------|
| `custom_menu_order` | Muss `true` zurückgeben, damit `menu_order` aktiv wird |
| `menu_order` | Array mit gewünschter Reihenfolge der Menü-Slugs |
| `admin_menu` (Prio 999) | `remove_menu_page()` nach allen Plugin-Registrierungen |

### Constraints

- mu-plugins werden automatisch geladen, brauchen keine WP-Aktivierung
- `remove_menu_page()` muss nach der Registrierung des Menüs laufen → Prio 999
- `wp_get_current_user()` ist im Admin-Context immer verfügbar

---

## Verification

### PHP-Syntax-Check

```bash
docker exec awz-wordpress php -l /var/www/html/wp-content/mu-plugins/awz-admin-menu.php
```

Erwartetes Ergebnis: `No syntax errors detected`

### Manuell (lokal, http://localhost:8080)

1. Docker starten: `docker compose up -d`
2. Als `admin` einloggen → volles Menü prüfen (alle Items da)
3. Als Wina (`w.benner@awz-bau.de`) einloggen → vereinfachtes Menü prüfen
4. **Prüfen:** Prio-Items oben in korrekter Reihenfolge
5. **Prüfen:** Ausgeblendete Items nicht sichtbar
6. **Prüfen:** Werkzeuge → Untermenüs (Lehrgangsreihenfolge, AWZ STEC Repair) erreichbar

---

## Rollback

- **Code:** `rm wp-content/mu-plugins/awz-admin-menu.php` auf Server → sofort wirksam, kein Neustart nötig
- **DB:** Kein DB-Eingriff
- **Git:** `git revert [commit-hash]` → Datei per FTP löschen

---

## Ask Before Proceeding

Keine — Agent kann selbstständig umsetzen.

---

## Related Docs

- `CLAUDE.md` – Architektur, Dev-Commands
- `docs/deployment.md` – FTP-Deployment-Workflow
