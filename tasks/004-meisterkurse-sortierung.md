# Task 004: Meisterkurse sortiert oben in Agenda-Liste

**Status:** Done
**Erstellt:** 2026-03-03
**Priorität:** Medium
**Risk:** Low

> Risk-Begründung: Entweder reine Backend-Konfiguration (kein Code) oder minimaler PHP-Filter. Kein DB-Schreibzugriff.

---

## Background

Die Agenda-Liste auf `/weiterbildung` sortiert Events chronologisch nach Startdatum. Wina möchte die Meisterkurse (Teil I bis IV) als Gruppe oben in der Liste, sortiert in der Reihenfolge I → II → III+IV → III → IV.

Problem: Teil I+II startet erst Jan 2027, während Teil III/IV schon ab April 2026 beginnt. Chronologisch erscheinen die Teile daher in falscher Reihenfolge.

---

## Approach

### Option A (bevorzugt, kein Code)

STEC-Backend prüfen, ob Events ein Reihenfolge-/Priority-Feld haben.

Falls ja: Wina setzt die Meisterkurse-Events auf passende Order-Werte und die Sortierung ergibt sich automatisch.

### Option B (minimaler Code, nur wenn Option A scheitert)

Ein PHP-Filter in `functions.php`, der STEC-Event-Queries nach `menu_order` vor Datum sortiert. Ca. 5 Zeilen PHP. Dann setzt Wina die Reihenfolge per WordPress-Schnellbearbeitung:

- Meisterkurse Teil I+II: Reihenfolge `1`
- Meisterkurse Teil III+IV: Reihenfolge `2`
- Meisterkurse Teil III: Reihenfolge `3`
- Meisterkurse Teil IV: Reihenfolge `4`
- Alle anderen Events: Reihenfolge `10` (oder Default)

**Erst Option A prüfen. Nur wenn kein Backend-Feld vorhanden, Option B umsetzen.**

---

## Scope

### Dateien, die geändert werden (nur Option B)

- `wp-content/themes/vantage-childtheme/functions.php` – `pre_get_posts`-Filter für `stec_event` Ordering

### Dateien, die NICHT geändert werden (off-limits)

- `wp-content/plugins/` – Plugins niemals direkt ändern

### Neue Dateien

Keine neuen Dateien.

---

## Acceptance Criteria

### Funktional

- [x] Meisterkurse stehen oben in der Agenda-Liste auf `/weiterbildung`
- [x] Reihenfolge: Teil I+II → Teil III+IV → Teil III → Teil IV
- [x] Alle anderen Lehrgänge bleiben chronologisch sortiert darunter
- [x] Reihenfolge kann von Wina im Backend angepasst werden (kein Hardcoding von Titeln)

### Code-Qualität (nur Option B)

- [x] PHP-Syntax-Check grün
- [x] Maximal 10 Zeilen neuer Code
- [x] Bestehende `stec_shortcode_atts`-Logik unverändert

### Non-Goals

- Visuelle Hervorhebung der Meisterkurse (kein anderes Styling)
- Automatische Erkennung von "Meister" im Titel (Sortierung soll über Order-Feld laufen)

---

## Technical Notes

### STEC Agenda-Liste Sortierung

Die Agenda-Liste wird über `stec_shortcode_atts` konfiguriert (Layout, Limit, Monate). Die Event-Reihenfolge wird intern von STEC über die REST-API bestimmt (`/wp-json/stec/v5/events`).

Falls STEC `menu_order` nicht nativ respektiert, muss geprüft werden ob:
1. Ein `pre_get_posts`-Filter für `stec_event` greift
2. Oder ob STEC eine eigene Query-Schicht hat, die WordPress-Hooks umgeht

### Option B – PHP-Filter Skizze

```php
add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query() && $query->get('post_type') === 'stec_event') {
        $query->set('orderby', ['menu_order' => 'ASC', 'date' => 'ASC']);
    }
});
```

**Achtung:** STEC nutzt eine eigene REST-API. Falls Events über REST geladen werden (wahrscheinlich), greift `pre_get_posts` möglicherweise nicht. In dem Fall müsste ein STEC-spezifischer Filter gefunden werden, oder der JS-DOM-Reorder-Ansatz als Fallback dienen.

---

## Verification

### Manuell (lokal, http://localhost:8080)

1. Docker starten: `docker compose up -d`
2. `http://localhost:8080/weiterbildung` aufrufen
3. **Prüfen:** Meisterkurse erscheinen als erste Einträge in der Liste
4. **Prüfen:** Reihenfolge I → II → III/IV → III → IV korrekt
5. **Prüfen:** Übrige Lehrgänge chronologisch sortiert

---

## Rollback

- **Code (Option B):** `git revert [commit-hash]` → FTP-Upload
- **Backend (Option A):** Order-Werte im WP-Admin zurücksetzen
- **DB:** Kein DB-Eingriff

---

## Implementierung

**Umgesetzt mit Option B (modifiziert):**

Option A (Backend-Feld) war nicht verfügbar – bestätigt durch Recherche des STEC-Quellcodes.

`pre_get_posts` (wie in der Task-Skizze) greift nicht, weil STEC eine eigene REST-API-Schicht nutzt.

**Tatsächliche Lösung** (durch Analyse von `events.js` Worker-Quellcode und REST-Controller):

1. **PHP-Filter `stec_event_controller_get_items`** injiziert WordPress `menu_order` in die REST-API-Antwort jedes Events.
2. **JS-Hook `window.stecFilterGetWorkerEventsBetween`** – ein undokumentierter STEC-Hook im Web-Worker – re-sortiert die Events **nach** dem internen Datum-Sort: Events mit `menu_order > 0` kommen zuerst (aufsteigend nach Wert), alle anderen bleiben nach Datum sortiert.

**Wina's Action:** WP Admin > Veranstaltung bearbeiten > Seitenleiste > Meta-Box **"Anzeigereihenfolge"**:
- Meisterkurs Teil I+II → `1`
- Meisterkurs Teil III+IV → `2`
- Meisterkurs Teil III → `3`
- Meisterkurs Teil IV → `4`
- Alle anderen Events → `0` (Default, keine Änderung nötig)

Gespeichert als Post-Meta `_awz_sort_order` (nicht menu_order – das ist bei Custom Post Types nicht in der UI verfügbar).

**Geänderte Dateien:** `functions.php` (ab Zeile 229 – 3 neue Filter/Action-Blöcke)

## Ask Before Proceeding

- [x] Vor Implementierung bestätigt: Option A nicht verfügbar → Option B umgesetzt

---

## Related Docs

- `CLAUDE.md` – `stec_shortcode_atts`-Filter Dokumentation
- `functions.php` Zeilen 188–212 – Bestehende Agenda-Liste-Konfiguration
