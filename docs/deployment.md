# Deployment Workflow

## Aktueller Stand: Commit `3ec52df` (2026-02-19)

Änderungen in diesem Stand:
- Skip-Cart: Direktweiterleitung zur Kasse nach "Buchen"-Klick
- Button-Text "In den Warenkorb" → "Buchen"
- Fehlermeldung "Ticketverkauf" → "Lehrgangsbuchung"
- `stec_allow_inprogress` im REST-Payload forciert

**FTP hochladen:**
```
wp-content/themes/vantage-childtheme/functions.php
wp-content/themes/vantage-childtheme/assets/css/stec-single-legacy.css
wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php
```

**DB-Fix auf Live ausführen** (phpMyAdmin, 3 Statements nacheinander):
`docs/fix-allow-inprogress-complete.sql`
→ Abschließendes SELECT muss 0 Zeilen zurückgeben.

Für Vorgeschichte der Booking-Bugs: `docs/stec-booking-handover-2026-02-15.md`

## Server Info

- **Host:** itbs2.it-schlabach.de
- **Zugang:** FTP (FileZilla)
- **SSH:** Unklar - bei IT Schlabach anfragen für erweiterte Optionen

## Pre-Deployment Checklist

- [ ] Lokaler Test erfolgreich (Docker Site)
- [ ] Git commit und push
- [ ] Live Site Backup erstellen (via phpMyAdmin oder FTP)

## Deployment via FTP

### 1. FileZilla öffnen und verbinden

- Host: `itbs2.it-schlabach.de`
- Benutzername: [siehe Passwort Manager]
- Passwort: [siehe Passwort Manager]
- Port: 21 (Standard FTP)

### 2. Theme Deployment

1. Lokales Verzeichnis: `/Users/patrick/Documents/Freelance/AWZ/Website/awz-website/wp-content/themes/vantage-childtheme/`
2. Remote Verzeichnis: `/wp-content/themes/vantage-childtheme/`
3. Geänderte Dateien auswählen und hochladen
4. File Permissions prüfen:
   - Dateien: 644
   - Ordner: 755

**Schneller:** Nur geänderte Dateien hochladen (FileZilla zeigt Unterschiede)

### Tipp: Git als Referenz

```bash
# Welche Dateien haben sich geändert?
git status
git diff --name-only

# Diese Dateien via FTP hochladen
```

## Plugin Updates

### STEC (Stachethemes Event Calendar)

Plugin-Update geht nicht per FTP-Ersatz alleine. Reihenfolge:

1. Altes Plugin via WP-Admin deaktivieren
2. Neues Plugin als ZIP hochladen (Plugins → Installieren → Plugin hochladen) oder via FTP ersetzen
3. Plugin aktivieren
4. **STEC Dashboard → Migrate** ausführen (Datenbankstruktur updaten)
5. **STEC Dashboard → Settings → Pages** → Event-URL-Slug auf `lehrgang` setzen
6. **Einstellungen → Permalinks** → Speichern (flusht Rewrite Rules)
7. Lizenz aktivieren falls nötig

Falls ZIP-Upload fehlschlägt ("Installationspaket nicht verfügbar"): FTP verwenden.

## Post-Deployment

- [ ] Live Site testen: www.awz-bau.de
- [ ] Als verschiedene User-Rollen testen (Admin, Besucher)
- [ ] Browser Cache clearen (Cmd+Shift+R)
- [ ] WordPress Cache clearen (WP Super Cache Plugin)
- [ ] Error Logs prüfen (via phpMyAdmin oder FTP: `/wp-content/debug.log`)
- [ ] **Kalender-Shortcode prüfen:** Seiten mit STEC-Kalender müssen `[stec]` verwenden (nicht `[stachethemes_ec]`)
- [ ] **AWZ STEC Repair Tool** ausführen falls Events nicht sichtbar: `/wp-admin/tools.php?page=awz-stec-repair` → Ticket-Flags Apply → Titel-Matching Apply

## Rollback

Falls etwas schief geht:

**Option A: FTP Backup zurückkopieren**
1. Backup Dateien von lokalem Backup-Ordner
2. Via FTP hochladen und überschreiben

**Option B: .wpress Backup importieren**
1. All-in-One WP Migration auf Live-Site
2. Backup .wpress importieren

## Erweiterung: SSH-Zugang anfragen

Falls SSH verfügbar, könnte Deployment vereinfacht werden:

**Mit SSH:**
```bash
# Git direkt auf Server
ssh user@itbs2.it-schlabach.de
cd /pfad/zu/wordpress/wp-content/themes/vantage-childtheme
git pull origin main
```

**Bei IT Schlabach anfragen:**
- SSH-Zugang möglich?
- Git auf dem Server installiert?
- WP-CLI verfügbar?

Dies würde ermöglichen:
- `git pull` statt FTP Upload
- Automatisierte Deployments via GitHub Actions
- Schnellere und sicherere Updates
