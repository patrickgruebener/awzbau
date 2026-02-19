-- ============================================================
-- FIX: "Der Verkauf ist beendet" - stec_allow_inprogress auf 1 setzen
-- Datum: 2026-02-16
-- ============================================================

-- Option 1: Update für alle stec_allow_inprogress Einträge (empfohlen)
-- Dies setzt alle allow_inprogress Werte auf 1
UPDATE wp_postmeta
SET meta_value = '1'
WHERE meta_key = 'stec_allow_inprogress';

-- Option 2: Nur für Produkte mit stec_ticket = 1 (selektiver)
-- Falls Option 1 zu viele Einträge betrifft:
/*
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
*/

-- ============================================================
-- ZUSÄTZLICH: Produkt aus Papierkorb wiederherstellen (falls nötig)
-- Produkt ID 11014 war im Backup als 'trash' markiert
-- ============================================================

/*
UPDATE wp_posts 
SET post_status = 'publish' 
WHERE ID = 11014 
  AND post_type = 'product' 
  AND post_status = 'trash';
*/

-- ============================================================
-- ROLLBACK (falls nötig):
-- UPDATE wp_postmeta SET meta_value = '0' WHERE meta_key = 'stec_allow_inprogress';
-- ============================================================

-- Überprüfung nach dem Update:
-- SELECT post_id, meta_value FROM wp_postmeta WHERE meta_key = 'stec_allow_inprogress' LIMIT 20;
