# Task 003: Ladekreis statt "Kalender lädt"-Text

**Status:** Done
**Erstellt:** 2026-03-03
**Priorität:** Medium
**Risk:** Low

> Risk-Begründung: Nur CSS-Änderung, kein PHP-Logik-Eingriff. Worst case: Spinner sieht falsch aus, Text bleibt unsichtbar.

---

## Background

STEC zeigt beim Laden der Eventliste auf `/weiterbildung` den deutschen Text "Kalender wird geladen" (Übersetzung von "Loading calendar" aus `stec-de_DE.po`). Wina möchte stattdessen einen visuellen Ladekreis (Spinner).

---

## Approach

CSS-only in `stec-single-legacy.css`:
1. Den Loading-Container-Text per CSS unsichtbar machen (`font-size: 0` oder `color: transparent`)
2. Einen animierten CSS-Spinner per `::after`-Pseudo-Element einfügen
3. Spinner im AWZ-Grün (`#639582`) gestalten

**Vorarbeit nötig:** Der exakte STEC-CSS-Selektor für den Loading-Container muss per Browser-Inspektion bestätigt werden. Vermutlich `.stec-loading`, `.stec-calendar-loading-screen` oder ähnlich.

**Vorgehen:**
1. Docker starten, `/weiterbildung` laden
2. Netzwerk drosseln (Chrome DevTools → Network → Slow 3G) um Ladezustand sichtbar zu machen
3. Element inspizieren → exakten Selektor notieren
4. CSS-Regel schreiben

---

## Scope

### Dateien, die geändert werden

- `wp-content/themes/vantage-childtheme/assets/css/stec-single-legacy.css` – Spinner-CSS + Text-Hiding

### Dateien, die NICHT geändert werden (off-limits)

- `wp-content/plugins/` – Plugins niemals direkt ändern
- `wp-content/themes/vantage-childtheme/inc/stec-i18n-fallback.php` – Translation-Override nicht nötig

### Neue Dateien

Keine neuen Dateien.

---

## Acceptance Criteria

### Funktional

- [ ] Beim Laden der `/weiterbildung`-Seite erscheint ein animierter Ladekreis (Spinner) statt Text
- [ ] Kein Text "Kalender wird geladen" oder "Kalender lädt" sichtbar
- [ ] Spinner ist im AWZ-Grün (`#639582`) oder neutralem Grau
- [ ] Spinner verschwindet wenn die Eventliste gerendert ist

### Code-Qualität

- [ ] Reine CSS-Lösung, kein JS nötig
- [ ] Kein `!important` wenn vermeidbar
- [ ] Bestehende Booking-Funktionen unverändert

### Non-Goals

- Translation-String ändern (nicht nötig wenn CSS den Text versteckt)
- Skeleton-Loading oder Shimmer-Effekt (einfacher Spinner reicht)

---

## Technical Notes

### STEC Loading State

Der Loading-State wird von STECs React-App gerendert. Der Text kommt aus der JS-Translation (`__('Loading calendar', 'stec')` → "Kalender wird geladen").

Die React-App ersetzt den Loading-Container durch die fertige Eventliste sobald die REST-API-Daten geladen sind. Der CSS-Spinner muss also am Loading-Container hängen, nicht am fertigen Kalender.

### CSS-Spinner Pattern

```css
/* Beispiel – Selektor muss nach Inspektion angepasst werden */
.stec-loading-container {
    font-size: 0;
    color: transparent;
    display: flex;
    justify-content: center;
    padding: 48px 0;
}

.stec-loading-container::after {
    content: '';
    width: 36px;
    height: 36px;
    border: 3px solid #e2e8e5;
    border-top-color: #639582;
    border-radius: 50%;
    animation: awz-spin 0.8s linear infinite;
}

@keyframes awz-spin {
    to { transform: rotate(360deg); }
}
```

---

## Verification

### Manuell (lokal, http://localhost:8080)

1. Docker starten: `docker compose up -d`
2. Chrome DevTools öffnen → Network → "Slow 3G" aktivieren (damit Ladezustand sichtbar)
3. `http://localhost:8080/weiterbildung` aufrufen (Hard Reload: Cmd+Shift+R)
4. **Prüfen:** Animierter grüner Spinner sichtbar während Kalender lädt
5. **Prüfen:** Kein Lade-Text sichtbar
6. **Prüfen:** Spinner verschwindet nach vollständigem Laden
7. Network-Throttling wieder deaktivieren

---

## Rollback

- **Code:** `git revert [commit-hash]` → FTP-Upload der reverted CSS-Datei
- **DB:** Kein DB-Eingriff

---

## Ask Before Proceeding

Keine – Agent kann selbstständig umsetzen, sobald Selektor bestätigt ist.

---

## Related Docs

- `CLAUDE.md` – STEC CSS-Override-Patterns (Custom Properties)
- `assets/css/stec-single-legacy.css` – Bestehende STEC-CSS-Overrides
