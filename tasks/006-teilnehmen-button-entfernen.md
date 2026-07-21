# Task 006: "Teilnehmen"-Button unter Events entfernen

**Status:** Draft
**Erstellt:** 2026-03-03
**Priorität:** Medium
**Risk:** Low

> Risk-Begründung: Nur CSS-Hiding, kein PHP-Eingriff. Worst case: Falscher Selektor, Button bleibt sichtbar.

---

## Background

STEC zeigt in der Agenda-Liste auf `/weiterbildung` unter jedem Event einen "Teilnehmen"-Button. Das ist die RSVP-Funktion von STEC ("Attend" → "Teilnehmen" via deutsche Übersetzung).

AWZ nutzt WooCommerce für Lehrgangsbuchungen, nicht STECs native RSVP. Der Button ist funktionslos und soll komplett verschwinden.

---

## Approach

CSS in `stec-single-legacy.css`: RSVP-Button per `display: none` ausblenden.

**Vorarbeit nötig:** Der exakte CSS-Selektor muss per Browser-Inspektion bestätigt werden. Vermutete Kandidaten:
- `.stec-event-preview-rsvp`
- `.stec-rsvp-button`
- `.stec-event-attend-button`
- Element mit Text "Teilnehmen" in der Event-Preview

**Vorgehen:**
1. Docker starten, `/weiterbildung` laden
2. Element "Teilnehmen" mit Rechtsklick inspizieren
3. Exakten Selektor notieren
4. CSS-Regel schreiben

---

## Scope

### Dateien, die geändert werden

- `wp-content/themes/vantage-childtheme/assets/css/stec-single-legacy.css` – RSVP-Button ausblenden

### Dateien, die NICHT geändert werden (off-limits)

- `wp-content/plugins/` – Plugins niemals direkt ändern
- `wp-content/themes/vantage-childtheme/inc/stec-i18n-fallback.php` – Translation nicht ändern

### Neue Dateien

Keine neuen Dateien.

---

## Acceptance Criteria

### Funktional

- [ ] Kein "Teilnehmen"-Button/-Link unter Events in der Agenda-Liste auf `/weiterbildung`
- [ ] Buchungs-Buttons auf den Einzel-Event-Seiten (`/lehrgang/[slug]/`) weiterhin sichtbar und funktional
- [ ] Kein Layout-Sprung durch das Entfernen (kein leerer Platzhalter)

### Code-Qualität

- [ ] Reine CSS-Lösung
- [ ] Selektor so spezifisch wie nötig, so allgemein wie möglich

### Non-Goals

- RSVP-Funktionalität in STEC deaktivieren (nur visuell verstecken)
- "Teilnehmen" auf Einzelseiten entfernen (nur in der Listenansicht)

---

## Technical Notes

### STEC Übersetzung

Die folgenden Translation-Entries in `stec-de_DE.po` erzeugen den "Teilnehmen"-Text:

| msgid | msgctxt | msgstr |
|-------|---------|--------|
| Attend | (ohne) | Teilnehmen |
| RSVP | Event preview rsvp button text | Teilnehmen |
| Subscribe | (ohne) | Teilnehmen |

Der Button in der Agenda-Liste ist der "Event preview rsvp button text".

### Bestehende Attendance-CSS

In `stec-single-legacy.css` Zeile 71 existiert bereits:
```css
.single-stec_event .stec-single-page .stec-event-attendance { ... }
```
Das betrifft die Einzelseite (`.single-stec_event`), nicht die Listenansicht.

---

## Verification

### Manuell (lokal, http://localhost:8080)

1. Docker starten: `docker compose up -d`
2. `http://localhost:8080/weiterbildung` aufrufen
3. **Prüfen:** Kein "Teilnehmen" unter den Event-Einträgen sichtbar
4. Einen Lehrgang anklicken → Einzelseite öffnen
5. **Prüfen:** "Buchen"-Button weiterhin sichtbar und funktional

---

## Rollback

- **Code:** `git revert [commit-hash]` → FTP-Upload der reverted CSS-Datei
- **DB:** Kein DB-Eingriff

---

## Ask Before Proceeding

Keine – Agent kann selbstständig umsetzen, sobald Selektor bestätigt ist.

---

## Related Docs

- `stec-single-legacy.css` – Bestehende STEC-CSS-Overrides
- `inc/stec-i18n-fallback.php` – STEC Übersetzungs-Fallback
