# Deployment Workflow

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

## Post-Deployment

- [ ] Live Site testen: www.awz-bau.de
- [ ] Als verschiedene User-Rollen testen (Admin, Besucher)
- [ ] Browser Cache clearen (Cmd+Shift+R)
- [ ] WordPress Cache clearen (WP Super Cache Plugin)
- [ ] Error Logs prüfen (via phpMyAdmin oder FTP: `/wp-content/debug.log`)

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
