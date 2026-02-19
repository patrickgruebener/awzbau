-- ============================================================
-- FIX: stec_allow_inprogress für ALLE Ticket-Produkte auf 1 setzen
-- Erstellt fehlende Einträge UND updated vorhandene
-- Datum: 2026-02-18
-- ============================================================

-- Schritt 1: Fehlende stec_allow_inprogress-Einträge einfügen
INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT DISTINCT p.ID, 'stec_allow_inprogress', '1'
FROM wp_posts p
INNER JOIN wp_postmeta pm_ticket ON p.ID = pm_ticket.post_id
  AND pm_ticket.meta_key = 'stec_ticket'
  AND pm_ticket.meta_value = '1'
LEFT JOIN wp_postmeta pm_allow ON p.ID = pm_allow.post_id
  AND pm_allow.meta_key = 'stec_allow_inprogress'
WHERE p.post_type = 'product'
  AND p.post_status = 'publish'
  AND pm_allow.meta_id IS NULL;

-- Schritt 2: Vorhandene stec_allow_inprogress-Einträge auf 1 setzen
UPDATE wp_postmeta pm
INNER JOIN wp_postmeta pm_ticket ON pm.post_id = pm_ticket.post_id
  AND pm_ticket.meta_key = 'stec_ticket'
  AND pm_ticket.meta_value = '1'
SET pm.meta_value = '1'
WHERE pm.meta_key = 'stec_allow_inprogress'
  AND pm.meta_value != '1';

-- ============================================================
-- Verifizierung: Nochmal Query 3 ausführen - sollte jetzt leer sein
-- ============================================================

SELECT
    p.ID as product_id,
    p.post_title as product_name,
    COALESCE(pm_allow.meta_value, 'FEHLT') as allow_inprogress_value
FROM wp_posts p
INNER JOIN wp_postmeta pm_ticket ON p.ID = pm_ticket.post_id AND pm_ticket.meta_key = 'stec_ticket' AND pm_ticket.meta_value = '1'
LEFT JOIN wp_postmeta pm_allow ON p.ID = pm_allow.post_id AND pm_allow.meta_key = 'stec_allow_inprogress'
WHERE p.post_type = 'product'
  AND p.post_status = 'publish'
  AND (pm_allow.meta_value IS NULL OR pm_allow.meta_value != '1')
ORDER BY p.post_title;

-- ERWARTETES ERGEBNIS: 0 Zeilen (alle Produkte haben jetzt allow_inprogress = 1)
