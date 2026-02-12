# AWZ Bau Website

WordPress Development Environment für www.awz-bau.de

## Setup

### Prerequisites
- Docker & Docker Compose installiert
- Git
- FileZilla (FTP-Zugang zu IT Schlabach Server)

### Lokale Entwicklung

1. Repository klonen:
```bash
git clone https://github.com/[USERNAME]/awz-website.git
cd awz-website
```

2. Docker Container starten:
```bash
docker-compose up -d
```

3. WordPress öffnen:
   - Frontend: http://localhost:8080
   - Admin: http://localhost:8080/wp-admin
   - phpMyAdmin: http://localhost:8081
   - Login: siehe Passwort Manager

4. Bei Änderungen automatisch sichtbar (Docker Volume Mounts)

## Struktur

```
awz-website/
├── docker-compose.yml         # Docker Setup
├── wp-content/
│   ├── themes/
│   │   └── vantage-childtheme/  # Custom Theme (Git tracked)
│   └── plugins/               # Marketplace Plugins (NICHT in Git)
├── database/
│   └── init.sql              # Initiale DB (nicht in Git)
└── docs/
    └── deployment.md         # FTP Deployment Guide
```

## Workflow

1. Docker Site starten: `docker-compose up -d`
2. Änderungen im Code machen (Theme)
3. Browser reload → Änderungen sofort sichtbar
4. Git commit & push
5. Deployment via FTP zu IT Schlabach Server (siehe docs/deployment.md)

## Theme

- **Aktives Theme:** vantage-childtheme (Custom Child Theme)
- **Parent Theme:** vantage (wird von Docker bereitgestellt)

## Plugins

Alle Plugins sind Marketplace/Premium Plugins und werden NICHT in Git getrackt.
Installation via WP Admin nach Docker Setup.

Wichtigste Plugins:
- WooCommerce
- STEC Event Calendar
- Contact Form 7
- Yoast SEO
- TablePress
- ...siehe vollständige Liste in phpMyAdmin

## Wichtig

- WordPress Core NICHT in Git (wird von Docker bereitgestellt)
- Marketplace Plugins NICHT in Git
- Database Exports in `/database/` (nicht in Git)
- .wpress Backups außerhalb Repo
