# Task 010: CF7 Anti-Spam verschärfen

**Status:** Draft
**Erstellt:** 2026-06-03
**Priorität:** High
**Größe:** S
**Risk:** Medium

> Risk-Begründung: Der Change greift in die serverseitige Formularvalidierung ein. Zu strenge Regeln können echte Kontaktanfragen blockieren, zu schwache Regeln lassen Winas Spamproblem bestehen.

---

## Background

Wina bekommt weiterhin Spam über das Kontaktformular von `www.awz-bau.de`, obwohl der bestehende MU-Plugin-Fix bereits kyrillische Link-Spam-Beispiele und Kommentar-Spam reduziert. Der neue bestätigte Fall vom 29.05.2026 enthält viele HTML-Links (`<a href=...>`) und Mojibake/kyrillisch wirkenden Text. Im aktuellen Code werden Tags vor der Link-Erkennung entfernt, wodurch HTML-Link-Spam teilweise nicht zuverlässig erkannt wird.

Ziel ist eine gezielte Verschärfung für Contact Form 7, ohne globale Geo-Sperre und ohne andere Formular-/Shop-Flows zu berühren.

---

## Approach

Den bestehenden MU-Plugin-Code in `awz-cf7-antispam.php` erweitern und entdoppeln: eine zentrale Prüflogik soll CF7-Freitextfelder serverseitig validieren, bevor eine Mail verschickt wird. Für Contact Form 7 sollen Links, HTML und fremde Schriftsysteme blockiert werden; E-Mail-Felder bleiben von diesen neuen Inhaltsregeln ausgenommen und werden weiter durch die normale CF7/E-Mail-Validierung behandelt.

Die Blockierung soll mit einer spezifischen Formularmeldung erfolgen:

> Ihre Nachricht enthält Links, HTML oder nicht erlaubte Zeichen. Bitte entfernen Sie diese Inhalte und senden Sie das Formular erneut.

---

## Scope

### Dateien, die geändert werden

- `wp-content/mu-plugins/awz-cf7-antispam.php` – bestehende CF7-Spam-Heuristiken verschärfen, ohne doppelte Logik aufzubauen
- `docs/deployment.md` – Deployment-Hinweise für den aktualisierten Anti-Spam-Stand ergänzen
- `tests/manual/post-deployment.md` – manuellen CF7-Anti-Spam-Test ergänzen

### Dateien, die NICHT geändert werden

- `wp-content/plugins/` – Plugins niemals direkt ändern, insbesondere nicht Contact Form 7
- `wp-content/themes/` – keine Theme-Änderung für diesen Task
- WooCommerce-, Checkout- und STEC-Code – nicht Teil des Spam-Fixes
- Server-/Firewall-/Geo-IP-Konfiguration – Geo-Zugriffskontrolle bleibt bewusst außen vor

### Neue Dateien

Keine neuen Code-Dateien. Falls sinnvoll, darf eine kleine Test-/Dokumentationsdatei unter `tests/manual/` ergänzt werden; bevorzugt ist aber die Erweiterung der bestehenden Post-Deployment-Checkliste.

---

## Acceptance Criteria

### Funktional

- [ ] Contact Form 7 blockt Formularübermittlungen, sobald ein Nicht-E-Mail-Feld HTML enthält, z.B. `<a href="https://example.com">Text</a>`.
- [ ] Contact Form 7 blockt Formularübermittlungen, sobald ein Nicht-E-Mail-Feld eine URL enthält, z.B. `https://example.com`, `http://example.com` oder `www.example.com`.
- [ ] Contact Form 7 blockt Formularübermittlungen, sobald ein Nicht-E-Mail-Feld fremde Schriftsysteme enthält, z.B. Kyrillisch, Arabisch, Chinesisch oder Griechisch.
- [ ] Deutsche Texte mit Umlauten, `ß`, Zahlen, Satzzeichen und normalen Sonderzeichen werden nicht wegen der neuen Regeln blockiert.
- [ ] E-Mail-Felder werden von der neuen HTML/URL/Schriftsystem-Prüfung ausgenommen.
- [ ] Eine blockierte CF7-Übermittlung zeigt die spezifische Meldung: `Ihre Nachricht enthält Links, HTML oder nicht erlaubte Zeichen. Bitte entfernen Sie diese Inhalte und senden Sie das Formular erneut.`
- [ ] Eine legitime Testnachricht über das Kontaktformular wird weiterhin zugestellt.
- [ ] Der bestätigte Spam-Typ aus Winas Mail vom 29.05.2026 wird geblockt: mehrere `<a href=...>` Links, Domain `hentai-xs.com`, kyrillisch/Mojibake-Text.

### Code-Qualität

- [ ] Bestehende Spam-Regeln bleiben erhalten, werden aber nicht unnötig doppelt ausgeführt.
- [ ] CF7-spezifische neue Regeln laufen nur für Contact Form 7, nicht für Kommentare, WooCommerce oder andere WordPress-Mails.
- [ ] PHP-Syntax-Check ist grün:

```bash
docker exec awz-wordpress php -l /var/www/html/wp-content/mu-plugins/awz-cf7-antispam.php
```

- [ ] Keine Debug-Ausgaben, `var_dump`, `error_log`-Reste oder temporären Testdaten committed.

### Non-Goals

- Keine Geo-IP- oder Länder-Zugriffskontrolle.
- Keine globale Website-Sperre für Zugriffe außerhalb Deutschlands.
- Keine Änderung an Contact Form 7 Plugin-Core-Dateien.
- Keine Änderung an WooCommerce-, Checkout-, STEC- oder Kommentarlogik außer dem Erhalt bestehender Anti-Spam-Regeln.
- Keine clientseitige JavaScript-only-Lösung; die Blockierung muss serverseitig greifen.

---

## Technical Notes

### Bestehender Code

- Relevante Datei: `wp-content/mu-plugins/awz-cf7-antispam.php`
- Bestehender Hook: `wpcf7_spam`
- Bestehende Funktion: `awz_antispam_text_is_spam()`
- Bestehende Kommentar-Schutzlogik bleibt erhalten, wird aber nicht mit den neuen CF7-Feldregeln vermischt.

### Vorgeschlagene technische Richtung

- Vor dem Strippen von Tags prüfen, ob ein Feld HTML-Tags oder HTML-Link-Strukturen enthält.
- URLs robust erkennen, mindestens:
  - `https://`
  - `http://`
  - `www.`
  - `href=`
- Fremde Schriftsysteme per Unicode-Script-Erkennung blockieren, ohne deutsche Zeichen zu treffen. Relevante Beispiele:
  - `\p{Cyrillic}`
  - `\p{Arabic}`
  - `\p{Han}`
  - `\p{Greek}`
  - optional weitere klare Nicht-Latin-Scripts, sofern risikoarm
- E-Mail-Felder anhand CF7-Feldtyp oder plausibler Feldnamen ausnehmen, nicht anhand pauschaler Textmuster allein.
- Fehlerausgabe bevorzugt über CF7-Validierungsfilter bzw. feldbezogene Invalidierung lösen, damit die spezifische Meldung im Formular erscheint. Falls `wpcf7_spam` für einzelne Fälle weiter genutzt wird, muss geprüft werden, ob die spezifische Meldung zuverlässig sichtbar ist.

### Constraints

- MU-Plugins werden automatisch geladen und müssen nicht im WP-Admin aktiviert werden.
- Der Live-Upload erfolgt laut Projektdoku per FTP.
- Vor Deployment muss ein Backup bzw. mindestens die bestehende Live-Datei gesichert werden.
- Der lokale Projektstand enthält bereits uncommitted Dateien; keine fremden Änderungen zurücksetzen.

---

## Verification

### Automatisiert / lokal

1. Docker-WordPress starten, falls noch nicht aktiv:

```bash
docker compose up -d
```

2. PHP-Syntax prüfen:

```bash
docker exec awz-wordpress php -l /var/www/html/wp-content/mu-plugins/awz-cf7-antispam.php
```

Erwartetes Ergebnis: `No syntax errors detected`

### Manuell (lokal, http://localhost:8080)

1. Kontaktformular aufrufen.
2. Normale Nachricht absenden:
   - Name: `Max Mustermann`
   - E-Mail: gültige Testadresse
   - Nachricht: `Guten Tag, ich interessiere mich für eine Weiterbildung und bitte um Rückruf.`
   - Erwartet: Formular wird akzeptiert und Mailversand nicht durch Anti-Spam blockiert.
3. HTML-Test absenden:
   - Nachricht: `<a href="https://example.com">Test</a>`
   - Erwartet: Formular wird blockiert und zeigt die spezifische Meldung.
4. URL-Test absenden:
   - Nachricht: `Bitte schauen Sie auf https://example.com`
   - Erwartet: Formular wird blockiert und zeigt die spezifische Meldung.
5. Fremdschrift-Test absenden:
   - Nachricht: `Смотреть аниме`
   - Erwartet: Formular wird blockiert und zeigt die spezifische Meldung.
6. Deutsch-Sonderzeichen-Test absenden:
   - Nachricht: `Straße, Prüfung, Grüße, 50 %, Rückruf bitte.`
   - Erwartet: Formular wird nicht wegen der neuen Regeln blockiert.
7. E-Mail-Feld-Test:
   - E-Mail: gültige Adresse mit normaler Domain
   - Nachricht: legitimer deutscher Text
   - Erwartet: E-Mail-Feld löst keine neue HTML/URL/Schriftsystem-Regel aus.

### Manuell (Live nach FTP-Upload)

1. `www.awz-bau.de` aufrufen und prüfen, dass die Seite ohne 500-Fehler lädt.
2. Kontaktformular mit legitimer Testnachricht absenden.
3. Kontaktformular mit URL/HTML/Fremdschrift-Test absenden.
4. Prüfen, ob die blockierten Tests nicht bei AWZ/Wina als Mail ankommen.
5. WordPress-Admin → Must-Use Plugins prüfen: `AWZ Anti-Spam` sichtbar.

---

## Rollback

- **Code:** vorherige Version von `wp-content/mu-plugins/awz-cf7-antispam.php` per FTP zurückspielen oder `git revert [commit-hash]` und reverted Datei hochladen.
- **DB:** Kein DB-Eingriff.
- **Konfiguration:** Keine Geo-/Server-Regeln, daher kein separater Infrastruktur-Rollback.
- **Sofortmaßnahme bei Fehlblockierung:** MU-Plugin-Datei auf Live temporär umbenennen oder vorherige Version hochladen.

---

## Ask Before Proceeding

Agent soll STOPPEN und fragen, bevor er handelt:

- [ ] FTP-Upload auf Live.
- [ ] Änderungen an `wp-content/plugins/`.
- [ ] Geo-IP-, Firewall-, `.htaccess`- oder Server-Regeln.
- [ ] Änderungen an WooCommerce, STEC, Checkout oder Theme-Dateien.
- [ ] Dauerhaftes Löschen oder Deaktivieren bestehender Anti-Spam-Regeln.

---

## Related Docs

- `docs/deployment.md` – FTP-Deployment-Workflow und aktueller Anti-Spam-Stand
- `tests/manual/post-deployment.md` – Live-Checkliste nach Deployment
- `wp-content/mu-plugins/awz-cf7-antispam.php` – bestehender Anti-Spam-Code
- Winas Mail vom 29.05.2026: `WG: Formular-Nachricht von AnnaLum AnnaLum von www.awz-bau.de`
