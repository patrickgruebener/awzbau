-- ============================================================
-- Lokale DB-Fixes für Buchungssystem
-- Kombiniert: stec_stop_before + stec_allow_inprogress
-- Datum: 2026-02-18
-- ============================================================

-- ============================================================
-- FIX 1: stec_stop_before von Sekunden zu Stunden normalisieren
-- ============================================================

-- Vorher prüfen (zeigt alle Produkte mit Sekunden-Werten):
SELECT meta_id, post_id, meta_value,
       CAST(CAST(meta_value AS UNSIGNED) / 3600 AS CHAR) AS normalized_hours
FROM wp_postmeta
WHERE meta_key = 'stec_stop_before'
  AND meta_value REGEXP '^[0-9]+$'
  AND CAST(meta_value AS UNSIGNED) >= 3600
  AND MOD(CAST(meta_value AS UNSIGNED), 3600) = 0;

-- Update ausführen (86400 Sekunden → 24 Stunden):
UPDATE wp_postmeta
SET meta_value = CAST(CAST(meta_value AS UNSIGNED) / 3600 AS CHAR)
WHERE meta_key = 'stec_stop_before'
  AND meta_value REGEXP '^[0-9]+$'
  AND CAST(meta_value AS UNSIGNED) >= 3600
  AND MOD(CAST(meta_value AS UNSIGNED), 3600) = 0;

-- Nachher prüfen:
SELECT meta_id, post_id, meta_value
FROM wp_postmeta
WHERE meta_key = 'stec_stop_before'
LIMIT 20;

-- ============================================================
-- FIX 2: stec_allow_inprogress auf 1 setzen
-- ============================================================

-- Vorher prüfen:
SELECT post_id, meta_value
FROM wp_postmeta
WHERE meta_key = 'stec_allow_inprogress'
LIMIT 20;

-- Update für alle Ticket-Produkte:
UPDATE wp_postmeta
SET meta_value = '1'
WHERE meta_key = 'stec_allow_inprogress'
  AND post_id IN (
    SELECT post_id FROM (
      SELECT DISTINCT post_id
      FROM wp_postmeta
      WHERE meta_key = 'stec_ticket'
        AND meta_value = '1'
    ) AS ticket_products
  );

-- Nachher prüfen:
SELECT post_id, meta_value
FROM wp_postmeta
WHERE meta_key = 'stec_allow_inprogress'
LIMIT 20;

-- ============================================================
-- ZUSAMMENFASSUNG ERWARTETER ÄNDERUNGEN
-- ============================================================
-- stec_stop_before: ~14 Zeilen (86400 → 24)
-- stec_allow_inprogress: Alle Ticket-Produkte auf 1 gesetzt
-- ============================================================
