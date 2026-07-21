# Epic 002: SEO-Optimierung für AWZ Event-Seiten

**Status:** Draft
**Erstellt:** 2026-02-23
**Priorität:** Medium
**Risk:** Low

> Risk-Einschätzung: Code-Änderungen sind reine Filter-Hooks ohne DB-Schreibzugriff. Yoast-Konfiguration im Admin ist nicht reversibel per Code-Rollback – daher vorher Status dokumentieren.

---

## Background

AWZ nutzt STEC v5 Events als primäre Landingpages für Lehrgangsbuchungen (`/lehrgang/[slug]/`). WooCommerce Ticket-Produkte (`/product/[slug]/`) sind Backend-Infrastruktur – kein nutzbarer Content, aber momentan öffentlich zugänglich.

Vier Probleme blockieren saubere Indexierung:

1. `stec_event` Post-Type ist in Yoast SEO nicht konfiguriert → Events haben kein `<title>`-Template, keine `<meta description>`, keinen Canonical-Tag, fehlen im XML-Sitemap.
2. Ticket-Produkte (`stec_ticket=1`) sind indexierbar → dünner Content, falsche Landingpages in Google, kannibalisieren Event-Seiten.
3. Ticket-Produkte erscheinen im WooCommerce Shop-Katalog und interner Suche.
4. Event JSON-LD Schema (Google Rich Results für Veranstaltungen) nicht geprüft/gesichert.

---

## Approach

**Track A (Yoast-Admin):** Post-Type `stec_event` aktivieren, Title-Template setzen, `product` deaktivieren, Sitemap konfigurieren.

**Track B (Code):** Neue Datei `inc/awz-seo.php` mit drei Filtern:
- `wp_robots` + `wpseo_robots_array` → noindex auf `stec_ticket=1` Produktseiten
- `wpseo_exclude_from_sitemap_by_post_ids` → Ticket-Produkte aus Yoast-Sitemap
- `woocommerce_product_is_visible` → Ticket-Produkte aus WC-Katalog

`functions.php` bekommt nur ein einziges `require_once` für die neue Datei.

**Track C (Verifikation + Deployment):** Lokale Tests, FTP, Google Rich Results Test, GSC.

---

## Tasks

### Track A – Yoast Admin (WP-Backend, manuell)

- [ ] **T-01** – stec_event in Yoast aktivieren
  - Yoast SEO → Search Appearance → Post Types → Lehrgang
  - "Show in search results": JA
  - SEO-Titel: `%%title%% | Ausbildungszentrum Westpfalz`
  - Meta-Beschreibung: leer lassen (nutzt Post-Excerpt)

- [ ] **T-02** – WooCommerce-Produkte in Yoast deaktivieren
  - Yoast SEO → Search Appearance → Post Types → Produkte
  - "Show in search results": NEIN (Backup zur Code-Ebene)

- [ ] **T-03** – XML-Sitemap konfigurieren
  - Yoast SEO → XML Sitemaps → Post Types
  - stec_event: eingeschlossen
  - product: ausgeschlossen

- [ ] **T-04** – Event-Excerpts pflegen (Content-Aufgabe, kein Code)
  - Pro STEC-Lehrgang 2–3 Sätze als Excerpt im WP-Editor
  - Wird als Meta-Description von Yoast genutzt

### Track B – Code-Änderungen Child Theme

- [ ] **T-05** – Neue Datei `inc/awz-seo.php` erstellen (enthält T-06, T-07, T-08)

- [ ] **T-06** – Noindex für Ticket-Produkte
  ```php
  // WordPress Core robots API
  add_filter( 'wp_robots', function ( $robots ) {
      if ( is_singular( 'product' ) && '1' === get_post_meta( get_the_ID(), 'stec_ticket', true ) ) {
          $robots['noindex'] = true;
          unset( $robots['max-image-preview'] );
      }
      return $robots;
  } );

  // Yoast-spezifischer Backup-Filter
  add_filter( 'wpseo_robots_array', function ( $robots ) {
      if ( is_singular( 'product' ) && '1' === get_post_meta( get_the_ID(), 'stec_ticket', true ) ) {
          return array( 'noindex' => 'noindex' );
      }
      return $robots;
  } );
  ```

- [ ] **T-07** – Ticket-Produkte aus Yoast-Sitemap ausschließen
  ```php
  add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function ( $ids ) {
      $ticket_ids = get_posts( array(
          'post_type'      => 'product',
          'post_status'    => 'publish',
          'meta_key'       => 'stec_ticket',
          'meta_value'     => '1',
          'posts_per_page' => -1,
          'fields'         => 'ids',
      ) );
      return array_merge( $ids, $ticket_ids );
  } );
  ```

- [ ] **T-08** – Ticket-Produkte aus WooCommerce-Katalog ausblenden
  ```php
  add_filter( 'woocommerce_product_is_visible', function ( $visible, $product_id ) {
      if ( '1' === get_post_meta( $product_id, 'stec_ticket', true ) ) {
          return false;
      }
      return $visible;
  }, 10, 2 );
  ```

- [ ] **T-09** – `functions.php`: require_once für `inc/awz-seo.php` hinzufügen
  Nach dem Block mit den anderen `require_once`-Aufrufen (nach Zeile ~29):
  ```php
  $awz_seo = get_stylesheet_directory() . '/inc/awz-seo.php';
  if ( file_exists( $awz_seo ) ) {
      require_once $awz_seo;
  }
  ```

- [ ] **T-10** – Event JSON-LD Schema prüfen, ggf. ergänzen
  - Event-Seite lokal aufrufen → Quelltext auf `"@type": "Event"` prüfen
  - Falls STEC vollständiges Schema liefert: nichts tun
  - Falls nicht: `wp_head`-Hook mit `EducationEvent`-JSON-LD in `inc/awz-seo.php` ergänzen (Start/Enddatum aus STEC-Postmeta auslesen)

### Track C – Verifikation und Deployment

- [ ] **T-11** – Lokale Tests (localhost:8080)
  - Event-Seite: Quelltext auf Yoast `<title>`, `<meta name="description">`, `<link rel="canonical">` prüfen
  - Ticket-Produkt-URL: `<meta name="robots" content="noindex, nofollow">` muss vorhanden sein
  - `/sitemap_index.xml`: `lehrgang-sitemap.xml` sichtbar, kein `product-sitemap.xml`
  - Shop-Seite: Ticket-Produkte nicht sichtbar

- [ ] **T-12** – PHP-Syntax-Check + FTP-Deployment
  ```bash
  docker exec awz-wordpress php -l /var/www/html/wp-content/themes/vantage-childtheme/functions.php
  docker exec awz-wordpress php -l /var/www/html/wp-content/themes/vantage-childtheme/inc/awz-seo.php
  ```
  Hochladen via FTP:
  - `wp-content/themes/vantage-childtheme/functions.php`
  - `wp-content/themes/vantage-childtheme/inc/awz-seo.php` (neu)

- [ ] **T-13** – Post-Deployment-Tests
  - Google Rich Results Test auf Event-URL
  - Google Search Console → URL-Inspection auf Event-URL
  - Yoast-Sitemap in GSC einreichen (falls noch nicht passiert)

---

## Scope

### Dateien, die geändert werden

- `wp-content/themes/vantage-childtheme/functions.php` – ein `require_once` ergänzen
- `wp-content/themes/vantage-childtheme/inc/awz-seo.php` – neue Datei (SEO-Filter)

### Dateien, die NICHT geändert werden (off-limits)

- `wp-content/plugins/` – Plugins niemals direkt ändern
- `wp-content/themes/vantage-childtheme/inc/stec-booking-repair.php` – Booking-Logik bleibt unberührt

### Neue Dateien

- `wp-content/themes/vantage-childtheme/inc/awz-seo.php`

---

## Acceptance Criteria

### Funktional

- [ ] Event-Seite `/lehrgang/[slug]/` hat Yoast-generiertes `<title>`-Tag mit Site-Name
- [ ] Event-Seite hat `<link rel="canonical">` auf die eigene URL
- [ ] Ticket-Produkt-URL hat `<meta name="robots" content="noindex">`
- [ ] `/sitemap_index.xml` enthält `lehrgang-sitemap.xml`
- [ ] `/sitemap_index.xml` enthält kein `product-sitemap.xml` (oder Produkte darin sind leer)
- [ ] Ticket-Produkte erscheinen nicht in der WooCommerce Shop-Seite

### Code-Qualität

- [ ] PHP-Syntax-Check grün für `functions.php` und `inc/awz-seo.php`
- [ ] Bestehender Buchungsflow unverändert (TC-01 bis TC-04 in `tests/manual/booking-flow.md` noch grün)
- [ ] Keine `console.log` oder Debug-Ausgaben

### Non-Goals

- Featured Images für Events hinzufügen (Content-Aufgabe)
- Vollständiges Event JSON-LD mit Datum/Ort (separater Task falls T-10 Ergänzungsbedarf zeigt)
- Google Search Console Setup / Sitemap-Ersteinreichung

---

## Technical Notes

### Relevante Hooks

| Hook | Datei | Zweck |
|------|-------|-------|
| `wp_robots` | awz-seo.php | Core-Robots-Directive für Ticket-Produkt-Seiten |
| `wpseo_robots_array` | awz-seo.php | Yoast-spezifischer noindex-Backup |
| `wpseo_exclude_from_sitemap_by_post_ids` | awz-seo.php | Sitemap-Ausschluss Ticket-Produkte |
| `woocommerce_product_is_visible` | awz-seo.php | WC-Katalog-Ausblendung |

### Patterns / Konventionen

- Alle neuen SEO-Logiken in `inc/awz-seo.php`, nicht direkt in `functions.php`
- Ticket-Erkennung ausschließlich via `get_post_meta($id, 'stec_ticket', true) === '1'`

### Constraints

- `wpseo_robots_array`-Filter existiert in Yoast >= 14.x. Falls ältere Version: nur `wp_robots` reicht.
- `wpseo_exclude_from_sitemap_by_post_ids` löst bei jedem Sitemap-Aufruf eine DB-Query aus. Unkritisch bei dieser Produktanzahl (~10–20 Tickets).

---

## Rollback

- **Code:** `git revert [commit-hash]` → FTP-Upload der reverted Dateien
- **Yoast-Config:** Kein automatischer Rollback möglich – Status vor T-01 manuell notieren
- **DB:** Kein DB-Eingriff durch Code-Änderungen dieses Epics

---

## Ask Before Proceeding

- [ ] FTP-Upload (T-12) nur nach bestandenem lokalen Test (T-11)
- Keine weiteren Eskalationspunkte – Code-Änderungen sind reine Filter-Hooks

---

## Related Docs

- `CLAUDE.md` – Architektur-Übersicht, Dev-Commands
- `docs/deployment.md` – FTP-Deployment-Workflow
- `tests/manual/booking-flow.md` – Buchungsflow-Checkliste
