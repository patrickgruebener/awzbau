# Task 009: AWZ Search Console – Duplikat-Warnungen prüfen

**Status:** Draft
**Erstellt:** 2026-03-07
**Priorität:** Medium
**Risk:** Low

> Risk-Begründung: Reine Diagnose und Dokumentation, keine Code-Änderungen erforderlich. Eventuell kanonische Tags in theme anpassen, falls nötig.

---

## Background

Google Search Console hat am 06.03.2026 zwei Warnungen für awz-bau.de gemeldet:
- "Duplikat – Google hat eine andere Seite als der Nutzer als kanonische Seite bestimmt"
- Zweite Meldung gleichen Typs kurz darauf

Verdacht: Bei der Kalender-Umstellung im AWZ-Projekt könnte etwas an der URL-Struktur oder den kanonischen Tags der Lehrbuchseiten geändert worden sein, was Google verwirrt.

Google Search Console Mails wurden archiviert unter Label `1-Auftraege/AWZ`.

---

## Approach

1. Google Search Console für awz-bau.de öffnen und die spezifischen Warnungen lokalisieren
2. Betroffene Seiten identifizieren (URLs, kanonische Tags prüfen)
3. Überprüfen, ob die Kalender-Umstellung URL-Änderungen oder Duplikate verursacht hat
4. Falls Problem: Ursache dokumentieren und Lösung implementieren (canonical-Tags anpassen oder URL-Struktur korrigieren)
5. Falls gewolltes Duplikat: Nachricht mit Begründung an Google senden

---

## Scope

### Dateien, die möglicherweise geändert werden

- `wp-content/themes/vantage-childtheme/functions.php` – Falls canonical-Tags programmatisch gesetzt werden müssen
- `wp-content/themes/vantage-childtheme/single-stec_event.php` – Template für Event-Seite (falls canonical-Tags dort nötig)

[Falls reine Diagnose: Keine Code-Änderungen]

### Dateien, die NICHT geändert werden (off-limits)

- `wp-content/plugins/` – Plugins niemals direkt ändern
- STEC-Plugin-Code

---

## Acceptance Criteria

### Funktional

- [ ] Betroffene Seiten aus Google Search Console identifiziert und dokumentiert
- [ ] Kanonische Tags auf den identifizierten Seiten überprüft
- [ ] Ursache der Duplikate geklärt (absichtlich oder unbeabsichtigt)
- [ ] Falls nötig: canonical-Tags angepasst oder URL-Struktur korrigiert
- [ ] Google Search Console zeigt keine unbeabsichtigten Duplikate mehr (oder: Duplikate erklärt)

### Non-Goals

- Keine großflächigen URL-Umstrukturierungen ohne spezifische Begründung
- Keine Änderungen an anderen Google Search Console Meldungen

---

## Verification

### Google Search Console Prüfung

1. Google Search Console aufrufen (awz-bau.de)
2. **Prüfen:** Unter "Abdeckung" oder "Verbesserungen" nach Duplikat-Warnungen suchen
3. **Notieren:** Betroffene URLs und deren kanonische Ziele
4. **Prüfen:** Browser-Inspektor → `<link rel="canonical" href="...">` auf Live-Seiten
5. Nach Änderungen: 3-5 Tage warten, dann Search Console neu prüfen

### Lokal (http://localhost:8080)

1. Docker starten: `docker compose up -d`
2. Betroffene Lehrbuch-Seite aufrufen
3. **Prüfen:** Canonical-Tag ist korrekt gesetzt (Inspektor → Page Source)

---

## Rollback

- **Code:** `git revert [commit-hash]` → FTP-Upload der reverted Dateien
- **DB:** Kein DB-Eingriff (reine Diagnose)

---

## Ask Before Proceeding

- [ ] Falls Code-Änderungen notwendig: Bestätigung für canonical-Tag Modifikation
- [ ] Falls URL-Umstrukturierung nötig: Strategie mit Patrick abstimmen

---

## Related Docs

- `CLAUDE.md` – Dev-Setup und Testing
- Archived GSC Mails: Label `1-Auftraege/AWZ` in Gmail
- `docs/deployment.md` – FTP-Deployment nach Code-Änderungen
