# Task 005: USP-Karten aktualisieren (3 Änderungen + neue Karte)

**Status:** Draft
**Erstellt:** 2026-03-03
**Priorität:** High
**Risk:** Low

> Risk-Begründung: Nur Textänderungen in PHP-Array + CSS-Flex-Anpassung. Kein DB-Zugriff, kein JS.

---

## Background

Wina möchte drei Änderungen an den USP-Karten auf `/weiterbildung` und eine neue fünfte Karte.

Aktuell 4 Karten:
1. Anerkannte Abschlüsse
2. Förderung möglich
3. Praxis statt Theorie ← umbenennen
4. Erfahrene Referenten ← umbenennen + Text ändern

Neu:
5. AWZ-Bildungskompass ← neue Karte

---

## Approach

1. PHP: `awz_usp`-Shortcode in `functions.php` (Zeile 147–182) – Array-Items anpassen und neues Item ergänzen
2. CSS: Flex-Basis von `calc(25% - 18px)` auf `calc(20% - 20px)` ändern, damit 5 Karten in eine Zeile passen

---

## Scope

### Dateien, die geändert werden

- `wp-content/themes/vantage-childtheme/functions.php` – USP-Shortcode Array (3 Änderungen + 1 neues Item)
- `wp-content/themes/vantage-childtheme/assets/css/stec-single-legacy.css` – Flex-Grid für 5 Karten

### Dateien, die NICHT geändert werden (off-limits)

- `wp-content/plugins/` – Plugins niemals direkt ändern

### Neue Dateien

Keine neuen Dateien.

---

## Änderungen im Detail

### 5a. "Praxis statt Theorie" → "Theorie und Praxis"

```php
// Vorher:
'title' => 'Praxis statt Theorie',
// Nachher:
'title' => 'Theorie und Praxis',
```

Icon (`fa-solid fa-helmet-safety`) und Text bleiben unverändert.

### 5b. "Erfahrene Referenten" → "Erfahrene Dozent*innen"

```php
// Vorher:
'title' => 'Erfahrene Referenten',
'text'  => 'Unsere Lehrkräfte kommen aus der Praxis und stehen auch nach dem Lehrgang mit Rat und Tat zur Seite.',

// Nachher:
'title' => 'Erfahrene Dozent*innen',
'text'  => 'Unsere Dozent*innen kommen aus der Praxis, sind Professor*innen und Dozent*innen der Universität Siegen, Ingenieur*innen des Landesbetrieb Straßen NRW, Meister*innen des AWZ Bau und erfahrene Führungskräfte der Bauwirtschaft.',
```

### 5c. Neue Karte: "AWZ-Bildungskompass"

Position: Fünftes Item im Array (nach "Erfahrene Dozent*innen", wie von Wina gewünscht "unten neben den Dozenten").

```php
array(
    'icon'  => 'fa-solid fa-compass',
    'title' => 'AWZ-Bildungskompass',
    'text'  => 'Ganz egal wann und wo Sie lernen möchten – der AWZ-Bildungskompass ist unsere digitale Lernplattform und ermöglicht Ihnen ein flexibles, ortsunabhängiges Zugreifen auf Unterlagen der Lehrgänge.',
),
```

### 5d. CSS: 5-Karten-Grid

```css
/* Vorher: */
.awz-usp-item { flex: 1 1 calc(25% - 18px); }

/* Nachher: */
.awz-usp-item { flex: 1 1 calc(20% - 20px); }
```

Tablet (max-width: 800px) und Mobile (max-width: 480px) bleiben unverändert.

---

## Acceptance Criteria

### Funktional

- [ ] Karte 3 zeigt Titel "Theorie und Praxis" (nicht mehr "Praxis statt Theorie")
- [ ] Karte 4 zeigt Titel "Erfahrene Dozent*innen" mit neuem Text (Universität Siegen, Landesbetrieb Straßen NRW etc.)
- [ ] Karte 5 "AWZ-Bildungskompass" erscheint mit Kompass-Icon und korrektem Text
- [ ] Alle 5 Karten auf Desktop in einer Zeile (bei ausreichender Viewport-Breite > 1000px)
- [ ] Tablet (< 800px): 2-3 Karten pro Zeile
- [ ] Mobile (< 480px): 1 Karte pro Zeile

### Code-Qualität

- [ ] PHP-Syntax-Check grün: `docker exec awz-wordpress php -l /var/www/html/wp-content/themes/vantage-childtheme/functions.php`
- [ ] Texte exakt wie von Wina vorgegeben (keine Umformulierungen)

### Non-Goals

- Icon-Farbe oder -Größe ändern
- USP-Karten-Reihenfolge ändern (bleibt: Abschlüsse, Förderung, Theorie+Praxis, Dozent*innen, Bildungskompass)

---

## Verification

### PHP-Syntax-Check (vor Commit)

```bash
docker exec awz-wordpress php -l /var/www/html/wp-content/themes/vantage-childtheme/functions.php
```

### Manuell (lokal, http://localhost:8080)

1. Docker starten: `docker compose up -d`
2. `http://localhost:8080/weiterbildung` aufrufen (Hard Reload: Cmd+Shift+R)
3. **Prüfen:** 5 USP-Karten sichtbar unterhalb der Lehrgangsliste
4. **Prüfen:** Titel und Texte exakt korrekt
5. **Prüfen:** Kompass-Icon bei der 5. Karte
6. **Prüfen:** Layout bei verschiedenen Bildschirmbreiten (Responsive)

---

## Rollback

- **Code:** `git revert [commit-hash]` → FTP-Upload der reverted Dateien
- **DB:** Kein DB-Eingriff

---

## Ask Before Proceeding

Keine – Agent kann selbstständig umsetzen.

---

## Related Docs

- `functions.php` Zeilen 147–182 – aktueller USP-Shortcode
- `stec-single-legacy.css` Zeilen 295–359 – aktuelles USP-CSS
