# AWZ Website - Setup Guide

Vollständige Schritt-für-Schritt Anleitung für das lokale Development Setup.

## Voraussetzungen

- **Docker Desktop** installiert und läuft
- **Git** installiert
- **Node.js** installiert (für .wpress Extraktion)
- **FileZilla** für FTP Deployment (optional)

## Initiales Setup (einmalig)

### 1. Repository klonen

```bash
git clone https://github.com/[USERNAME]/awz-website.git
cd awz-website
```

### 2. .wpress Backup besorgen

Falls noch nicht vorhanden, .wpress Backup von Live-Site erstellen:
- All-in-One WP Migration Plugin auf Live-Site
- Export erstellen
- .wpress Datei herunterladen

### 3. Datenbank und Uploads vorbereiten

Wenn du ein neues .wpress Backup importieren musst:

```bash
# .wpress Datei extrahieren
npx wpress-extract backup.wpress -o temp-extract

# Datenbank kopieren und URLs anpassen
cp temp-extract/database.sql database/init.sql
sed -i '' 's|https://www.awz-bau.de|http://localhost:8080|g' database/init.sql
sed -i '' 's|http://www.awz-bau.de|http://localhost:8080|g' database/init.sql

# Uploads kopieren
cp -r temp-extract/uploads/* wp-content/uploads/

# Plugins kopieren (falls neu)
cp -r temp-extract/plugins/* wp-content/plugins/

# Parent Theme kopieren (falls fehlt)
cp -r temp-extract/themes/vantage wp-content/themes/

# Cleanup
rm -rf temp-extract/
```

### 4. Docker Container starten

```bash
docker compose up -d
```

Container Status prüfen:
```bash
docker ps --filter "name=awz"
```

Sollte zeigen:
- awz-wordpress (Port 8080)
- awz-db (Port 3307)
- awz-phpmyadmin (Port 8085)

### 5. WordPress öffnen

- Frontend: http://localhost:8080
- Admin: http://localhost:8080/wp-admin
- phpMyAdmin: http://localhost:8085

**Login Daten:** Siehe Passwort Manager oder aus Live-Site

## Täglicher Workflow

### Container starten

```bash
cd /Users/patrick/Documents/Freelance/AWZ/Website/awz-website
docker compose up -d
```

### Container stoppen

```bash
docker compose down
```

### Logs anschauen

```bash
docker compose logs -f wordpress
docker compose logs -f db
```

### Theme bearbeiten

```bash
cd wp-content/themes/vantage-childtheme/
# Datei bearbeiten in VS Code oder anderem Editor
# Browser reload → Änderung sofort sichtbar
```

### Git Commit

```bash
git add .
git commit -m "Fix: Beschreibung der Änderung"
git push
```

### Live Deployment

Siehe [docs/deployment.md](docs/deployment.md) für FTP Deployment Workflow.

## Troubleshooting

### WordPress zeigt Install-Dialog

Problem: Datenbank nicht richtig importiert.

Lösung:
```bash
# Container neu starten mit frischer Datenbank
docker compose down -v
docker compose up -d
```

### Port bereits belegt

Problem: Port 8080, 8085 oder 3307 bereits verwendet.

Lösung: In `docker-compose.yml` andere Ports verwenden.

### Plugins fehlen

Problem: Nach Git Clone fehlen Marketplace Plugins.

Lösung: Entweder aus Backup kopieren oder via WP Admin neu installieren.

### Theme zeigt keine Änderungen

Problem: Browser Cache oder WordPress Cache.

Lösung:
1. Hard Reload: Cmd+Shift+R
2. WordPress Cache löschen (WP Super Cache Plugin)
3. Docker Container neu starten

### STEC Plugin Update (v3 → v5)

STEC v5 ist ein kompletter Rewrite (React statt jQuery). Bei einem Update sind folgende Schritte nötig:

1. **Lokal testen zuerst** — niemals direkt live updaten
2. **Plugin-Hauptdatei hat sich geändert:** `stachethemes_event_calendar.php` → `stec.php`. WordPress erkennt das als anderes Plugin. Deshalb nach dem Upload: Plugin im WP-Admin deaktivieren und neu aktivieren.
3. **Migration ausführen:** STEC Dashboard → Migrate (einmalig pro Datenbank — lokal UND live)
4. **Event-URL-Slug setzen:** STEC Dashboard → Settings → Pages → "Events page slug" → `lehrgang`
5. **Rewrite Rules flushen:** Einstellungen → Permalinks → Speichern
6. **Lizenz aktivieren:** STEC Dashboard → Activator → License Key eintragen

**Shortcode hat sich geändert:** `[stachethemes_ec]` → `[stec]`. Alle Seiten mit dem alten Shortcode anpassen.

**Rollback möglich:** Backup des alten Plugins liegt unter `/Users/patrick/Documents/Freelance/AWZ/Website/stachethemes_event_calendar_v3.2.4_backup/`. Alte Events bleiben in der DB (Status `stec_legacy`), werden von v3 direkt wieder gelesen.

**Plugin-Upload via ZIP:** Funktioniert über WP-Admin → Plugins → Installieren → Plugin hochladen. Falls "Installationspaket nicht verfügbar": FTP verwenden.

### STEC v5: DB-Option für Event-Slug

v5 liest den Event-Slug aus der Option `stec_settings` (JSON/serialized). Falls die Option nicht existiert, greift der Default `stec_event`. Entweder im STEC-Dashboard setzen oder per PHP:

```php
// In functions.php: Fallback falls stec_settings-Option nicht gesetzt
add_filter('stec_default_settings', function ($settings) {
    $settings['pages']['events_page_slug'] = 'lehrgang';
    return $settings;
});
```

**Wichtig:** Dieser Filter greift nur wenn keine `stec_settings` DB-Option existiert. Sobald die Option in der DB gespeichert ist, überschreibt sie den Filter.

### Plugin-CSS Overrides greifen nicht

Problem: CSS in `style.css` hat keine Wirkung auf Plugin-Elemente (z.B. STEC Event Calendar).

Ursache: Manche Plugins (z.B. STEC) generieren Inline-`<style>`-Blöcke via PHP, die nach dem Theme-CSS geladen werden und es überschreiben.

**STEC v3:** Spezifische Selektoren via `wp_head` mit Priorität 999.

**STEC v5:** CSS Custom Properties nutzen — deutlich sauberer:
```php
add_action('wp_head', function () {
    ?>
    <style>
        .stec {
            --stec-top-menu-bg-active-primary: #639582;
            --stec-top-menu-bg-active-secondary: #527a6b;
        }
    </style>
    <?php
}, 999);
```

## Datenbank Sync

### Von Live zu Lokal

```bash
# 1. Live DB Export via phpMyAdmin
# 2. Download via FTP
# 3. In LocalWP importieren:
docker cp backup.sql awz-db:/tmp/
docker exec awz-db mysql -u wordpress -pwordpress wordpress < /tmp/backup.sql

# 4. URLs anpassen
docker exec awz-wordpress wp search-replace 'https://www.awz-bau.de' 'http://localhost:8080' --all-tables --allow-root
```

### Von Lokal zu Backup

```bash
docker exec awz-wordpress wp db export /var/www/html/database/backup-$(date +%Y%m%d).sql --allow-root
# Datei liegt dann in: awz-website/database/backup-YYYYMMDD.sql
```

## Container Management

### Alles neu aufsetzen

```bash
# Alle Container und Volumes löschen
docker compose down -v

# Neu starten (importiert database/init.sql automatisch)
docker compose up -d
```

### Einzelne Container neu starten

```bash
docker compose restart wordpress
docker compose restart db
```

### Container Shell öffnen

```bash
docker exec -it awz-wordpress bash
docker exec -it awz-db bash
```

## Häufige Befehle

```bash
# Container Status
docker compose ps

# Container Logs
docker compose logs -f

# Container stoppen
docker compose stop

# Container löschen (Daten bleiben)
docker compose down

# Container + Volumes löschen (Alles weg!)
docker compose down -v

# Container neu bauen
docker compose up -d --build
```

## Nützliche Links

- **WordPress Admin:** http://localhost:8080/wp-admin
- **Frontend:** http://localhost:8080
- **phpMyAdmin:** http://localhost:8085
- **Live Site:** https://www.awz-bau.de

## Support

Bei Problemen:
1. Logs prüfen: `docker compose logs -f`
2. Container neu starten: `docker compose restart`
3. Im Zweifelsfall: `docker compose down -v && docker compose up -d`
