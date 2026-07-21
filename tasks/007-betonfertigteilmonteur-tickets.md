# Task 007: Betonfertigteilmonteur – Tickets A, B, C nicht buchbar

**Status:** Draft
**Erstellt:** 2026-03-03
**Priorität:** High
**Risk:** Medium

> Risk-Begründung: Betrifft Buchungsflow. Tickets müssen korrekt mit STEC-Event verknüpft sein, sonst kein Checkout möglich. Änderungen nur lokal testen, dann Produktiv.

---

## Background

Der Betonfertigteilmonteur-Lehrgang hat drei Ticket-Typen (A, B und C), die nicht buchbar sind. Wina vermutet, dass die Tickets neu eingebunden werden müssen.

---

## Approach

Rein auf Admin-Ebene (kein Code nötig, außer falls WooCommerce-Produkte komplett fehlen).

### Diagnoseschritte

1. **WooCommerce-Produkte prüfen:**
   - WP Admin → WooCommerce → Produkte → nach "Betonfertigteil" filtern
   - Existieren 3 Produkte (A, B, C)?
   - Status: Veröffentlicht?
   - Produkttyp: Einfaches Produkt + Virtuell?
   - Custom Meta `stec_ticket = 1` vorhanden?

2. **STEC-Event prüfen:**
   - WP Admin → STEC Events → Betonfertigteilmonteur öffnen
   - Sind Tickets/Produkte im Event verknüpft?
   - Event-Status: Veröffentlicht?

3. **Repair-Tool nutzen:**
   - `/wp-admin/tools.php?page=awz-stec-repair` öffnen
   - "Titel-Matching" → Dry Run ausführen
   - Prüfen ob Betonfertigteilmonteur-Event mit Tickets A/B/C gematcht wird
   - Falls Match korrekt: Apply ausführen

4. **Falls Produkte fehlen (manuell erstellen):**
   - 3 neue WooCommerce-Produkte erstellen:
     - "Betonfertigteilmonteur A" (o.ä.)
     - "Betonfertigteilmonteur B"
     - "Betonfertigteilmonteur C"
   - Jeweils: Einfaches Produkt, Virtuell, Preis setzen
   - Custom Meta: `stec_ticket = 1`
   - Im STEC-Event als Tickets verknüpfen

### SQL-Diagnose (optional, für schnellen Überblick)

```sql
-- Alle WooCommerce-Produkte mit "Betonfertigteil" im Titel
SELECT p.ID, p.post_title, p.post_status,
       MAX(CASE WHEN pm.meta_key = 'stec_ticket' THEN pm.meta_value END) AS stec_ticket,
       MAX(CASE WHEN pm.meta_key = '_virtual' THEN pm.meta_value END) AS is_virtual
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'product'
  AND p.post_title LIKE '%Betonfertigteil%'
GROUP BY p.ID;

-- STEC-Event "Betonfertigteilmonteur" + verknüpfte Produkte
SELECT p.ID, p.post_title, p.post_status,
       pm.meta_key, pm.meta_value
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'stec_event'
  AND p.post_title LIKE '%Betonfertigteil%'
  AND pm.meta_key LIKE 'stec_%';
```

---

## Scope

### Dateien, die geändert werden

Keine Code-Änderungen geplant. Reine Admin/DB-Aufgabe.

### Dateien, die NICHT geändert werden (off-limits)

- `wp-content/plugins/` – Plugins niemals direkt ändern
- `wp-content/themes/vantage-childtheme/` – Kein Theme-Code betroffen

---

## Acceptance Criteria

### Funktional

- [ ] Betonfertigteilmonteur-Eventseite zeigt 3 buchbare Tickets (A, B, C)
- [ ] Klick auf "Buchen" bei jedem Ticket leitet zur Kasse weiter
- [ ] Checkout mit jedem der 3 Tickets funktional (Testbestellung lokal)

### Non-Goals

- Ticketpreise oder -beschreibungen ändern (das macht Wina im WP-Admin)
- Andere Events prüfen (nur Betonfertigteilmonteur)

---

## Verification

### Manuell (lokal, http://localhost:8080)

1. Docker starten: `docker compose up -d`
2. Betonfertigteilmonteur-Eventseite aufrufen (`/lehrgang/betonfertigteilmonteur*/`)
3. **Prüfen:** 3 Ticket-Optionen (A, B, C) sichtbar
4. **Prüfen:** "Buchen"-Button bei jedem Ticket aktiv (nicht ausgegraut)
5. Ticket A buchen → Checkout erreicht
6. Ticket B buchen → Checkout erreicht
7. Ticket C buchen → Checkout erreicht

---

## Rollback

- **Produkte:** WooCommerce-Produkte auf "Entwurf" setzen falls falsch erstellt
- **DB:** Kein destruktiver DB-Eingriff, nur Diagnose-Queries

---

## Ask Before Proceeding

- [ ] Vor Änderungen an Live-Datenbank oder Live-Produkten: Bestätigung einholen
- [ ] Falls Produkte komplett neu erstellt werden müssen: Preise und Beschreibungen von Wina bestätigen lassen

---

## Related Docs

- `CLAUDE.md` – Admin-Repair-Tool Dokumentation
- `inc/stec-booking-repair.php` – Titel-Matching-Logik und Ticket-Flag-Repair
- `docs/diagnose-ticket-meta.sql` – Allgemeine Ticket-Meta-Diagnose
