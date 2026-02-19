-- ============================================================
-- Diagnose: Ticket-Meta für alle STEC-Produkte prüfen
-- Datum: 2026-02-18
-- ============================================================

-- 1. Alle Ticket-Produkte mit ihren wichtigen Meta-Feldern anzeigen
SELECT
    p.ID as product_id,
    p.post_title as product_name,
    p.post_status,
    MAX(CASE WHEN pm.meta_key = 'stec_ticket' THEN pm.meta_value END) as is_ticket,
    MAX(CASE WHEN pm.meta_key = 'stec_stop_before' THEN pm.meta_value END) as stop_before,
    MAX(CASE WHEN pm.meta_key = 'stec_allow_inprogress' THEN pm.meta_value END) as allow_inprogress,
    MAX(CASE WHEN pm.meta_key = 'stec_manage_stock' THEN pm.meta_value END) as manage_stock,
    MAX(CASE WHEN pm.meta_key = 'stec_per_order_limit' THEN pm.meta_value END) as per_order_limit,
    MAX(CASE WHEN pm.meta_key = '_sale_price_dates_from' THEN pm.meta_value END) as sale_from,
    MAX(CASE WHEN pm.meta_key = '_sale_price_dates_to' THEN pm.meta_value END) as sale_to
FROM wp_posts p
INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'product'
  AND p.post_status IN ('publish', 'draft', 'pending')
  AND pm.meta_key IN (
    'stec_ticket',
    'stec_stop_before',
    'stec_allow_inprogress',
    'stec_manage_stock',
    'stec_per_order_limit',
    '_sale_price_dates_from',
    '_sale_price_dates_to'
  )
GROUP BY p.ID
HAVING is_ticket = '1'
ORDER BY p.post_title;

-- ============================================================

-- 2. Problematische Produkte finden (Sekunden statt Stunden)
SELECT
    p.ID as product_id,
    p.post_title as product_name,
    pm.meta_value as stop_before_value,
    CASE
        WHEN CAST(pm.meta_value AS UNSIGNED) >= 3600 THEN 'PROBLEM: Wert in Sekunden!'
        ELSE 'OK'
    END as status
FROM wp_posts p
INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'product'
  AND pm.meta_key = 'stec_stop_before'
  AND pm.meta_value REGEXP '^[0-9]+$'
  AND CAST(pm.meta_value AS UNSIGNED) > 0
ORDER BY CAST(pm.meta_value AS UNSIGNED) DESC;

-- ============================================================

-- 3. Produkte ohne allow_inprogress = 1 finden
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

-- ============================================================

-- 4. Events und ihre zugeordneten Produkte
SELECT
    e.ID as event_id,
    e.post_title as event_name,
    e.post_status as event_status,
    pm_products.meta_value as product_ids
FROM wp_posts e
LEFT JOIN wp_postmeta pm_products ON e.ID = pm_products.post_id AND pm_products.meta_key = 'products'
WHERE e.post_type = 'stec_event'
  AND e.post_status IN ('publish', 'stec_legacy')
ORDER BY e.post_title;

-- ============================================================
-- ERWARTETE ERGEBNISSE:
-- Query 1: Alle Tickets mit vollständigen Meta-Daten
-- Query 2: Keine Einträge mit "PROBLEM" (alle stop_before < 3600)
-- Query 3: Keine Einträge (alle haben allow_inprogress = 1)
-- Query 4: Alle Events mit ihren Produkt-IDs
-- ============================================================
