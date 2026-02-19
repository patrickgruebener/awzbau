# Checkliste: Post-Deployment (Live)

Pflichtcheck nach jedem FTP-Deployment auf www.awz-bau.de.

**Vollständiger Deployment-Workflow:** `docs/deployment.md`

---

## Sofort nach Upload

- [ ] Browser-Cache leeren: Cmd+Shift+R (oder Inkognito-Fenster)
- [ ] www.awz-bau.de aufrufen → Seite lädt ohne 500-Fehler
- [ ] WordPress-Admin aufrufbar: /wp-admin/

## Kalender & Events

- [ ] Weiterbildungsseite öffnen → Kalender wird angezeigt
- [ ] Events sind sichtbar (nicht "Keine Veranstaltungen gefunden")
- [ ] Event-Klick öffnet Einzelseite unter `/lehrgang/[slug]/`
- [ ] Network-Tab auf STEC-Seiten: keine `stec-de_DE-*.json` 404 Kette

## Buchungsflow (Kernfunktion)

- [ ] Beliebige Event-Seite aufrufen
- [ ] "Buchen"-Button sichtbar (nicht "Add to cart")
- [ ] Klick → direkter Redirect zu `/checkout/`
- [ ] Checkout: deutsche Labels korrekt
- [ ] Checkout: kein Produktbild, Produktname nicht verlinkt

## Wenn Booking-Logik deployed wurde

- [ ] AWZ STEC Repair Tool aufrufen: /wp-admin/tools.php?page=awz-stec-repair
- [ ] Booking Repair → Dry Run → Ergebnis prüfen → ggf. Apply
- [ ] Ticket-Flags → Dry Run → Ergebnis prüfen → ggf. Apply

## Rollback

Bei kritischen Fehlern:

1. Backup-Dateien via FTP zurückspielen (`backups/vantage-childtheme-live-backup-YYYY-MM-DD/`)
2. Oder DB-Backup importieren via phpMyAdmin (Live: itbs2.it-schlabach.de phpMyAdmin)
