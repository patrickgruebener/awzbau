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

## Kontaktformular & Anti-Spam

- [ ] Kontaktformular mit legitimer Nachricht absenden: `Guten Tag, ich interessiere mich für eine Weiterbildung und bitte um Rückruf.` → Formular wird akzeptiert
- [ ] HTML-Test absenden: `<a href="https://example.com">Test</a>` → Formular wird blockiert
- [ ] URL-Test absenden: `Bitte schauen Sie auf https://example.com` → Formular wird blockiert
- [ ] Fremdschrift-Test absenden: `Смотреть аниме` → Formular wird blockiert
- [ ] Deutsch-Sonderzeichen-Test absenden: `Straße, Prüfung, Grüße, 50 %, Rückruf bitte.` → wird nicht wegen Anti-Spam blockiert
- [ ] Blockierte Tests zeigen die Meldung: `Ihre Nachricht enthält Links, HTML oder nicht erlaubte Zeichen. Bitte entfernen Sie diese Inhalte und senden Sie das Formular erneut.`
- [ ] Prüfen, dass blockierte URL/HTML/Fremdschrift-Tests nicht als Mail bei AWZ/Wina ankommen

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
