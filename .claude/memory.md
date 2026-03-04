# Project Memory

Append-only log. Date | What | Why. Keep entries short.
Do NOT duplicate what is in CLAUDE.md.

---

- 2026-03-04 | WooCommerce Orders ist für Admin-User immer WC-Untermenü (nie Top-Level) | WC registriert shop_order mit show_in_menu='woocommerce' für edit_others_shop_orders — Workaround: add_menu_page() mit Slug 'edit.php?post_type=shop_order' explizit registrieren
- 2026-03-04 | SiteOrigin Menü-Slug ist volle URL: admin.php?page=siteorigin-installer | remove_menu_page('siteorigin-installer') funktioniert nicht — nur volle URL als Slug klappt
- 2026-03-04 | mu-plugins war nicht in Docker gemountet | docker-compose.yml hatte kein mu-plugins Volume — hinzugefügt: ./wp-content/mu-plugins:/var/www/html/wp-content/mu-plugins
- 2026-03-04 | WP-CLI nicht im PATH im Container | docker exec awz-wordpress wp ... schlägt fehl — stattdessen php -r '...' oder docker cp für Syntax-Checks nutzen

