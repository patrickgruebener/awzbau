# STEC Performance Overrides (AWZ)

## Zweck

Diese Doku beschreibt die upgrade-sicheren Performance-Massnahmen fuer den Kalender auf `/weiterbildung`, ohne Core-Aenderungen im Plugin `stachethemes_event_calendar`.

## Root-Cause Zusammenfassung

1. Hoher Initial-Load fuer Events (`events_per_request` standardmaessig hoch).
2. Schwere Event-Payloads (inkl. umfangreicher Inhalte/Meta-Daten).
3. Teure REST-Abfragen auf Event- und Meta-Daten.
4. Zusaetzlicher Overhead durch globale Uebersetzungsfilter.

## Umgesetzte Aenderungen

### 1) MU-Plugin als zentrale Performance-Schicht

Datei: `wp-content/mu-plugins/awz-stec-performance.php`

Implementiert:

1. `stec_shortcode_atts` Override nur fuer `/weiterbildung`:
   - `misc__events_per_request = 20`
   - `misc__events_prefetch = false`
   - `filter__min_date = heute 00:00`
   - `filter__max_date = heute +12 Monate 23:59`
2. REST-Response-Caching fuer `GET /stec/vX/events`:
   - TTL: `300s`
   - Cache-Key basiert auf:
     - Route
     - Query-Parametern (normalisiert/sortiert)
     - Sprache (`lang` / Locale)
     - Login-Status
     - User-Kontext (`user_id`)
     - Cache-Version
3. Optionaler Debug-Header:
   - `X-AWZ-STEC-Cache: HIT|MISS`
4. Cache-Invalidierung ueber Versionszaehler:
   - `save_post_stec_event`
   - `deleted_post` (nur `stec_event`)
   - `trashed_post` (nur `stec_event`)
   - `set_object_terms` fuer `stec_cal`, `stec_cat`, `stec_org`, `stec_loc`, `stec_gst` bei `stec_event`

### 2) MU-Hotfix fuer Jetpack i18n-loader (Live Root Cause)

Datei: `wp-content/mu-plugins/awz-stec-i18n-loader-hotfix.php`

Root Cause (Live):

1. Auf STEC-Seiten wurde ein WooCommerce/Jetpack `i18n-loader.js` geladen.
2. Dieser Loader hat `stec-de_DE-*.json` in `wp-content/languages/plugins/` angefragt.
3. Die Dateien waren nicht vorhanden, es entstanden viele langsame `404` Requests.
4. Dadurch erschien Kalender/Event-UI stark verspaetet.

Implementiert:

1. Patch von `window.wp.jpI18nLoader.downloadI18n` nur im STEC-Frontend-Kontext.
2. Fuer Domain `stec` wird der Fetch uebersprungen (`return`), andere Domains bleiben unveraendert.
3. Schutzflag `__awzStecHotfixApplied` verhindert doppelte Patch-Ausfuehrung.
4. Rollback jederzeit durch Entfernen/Umbenennen der MU-Datei.

### 3) Child-Theme Translation Guards

Dateien:

1. `wp-content/themes/vantage-childtheme/functions.php`
2. `wp-content/themes/vantage-childtheme/inc/stec-i18n-fallback.php`

Implementiert:

1. Fruehe Guards fuer Frontend-only Kontexte.
2. STEC-Fallback nur fuer relevante STEC-Handles.
3. WooCommerce-Textfallback nur fuer Checkout/Order-Kontexte.
4. Keine inhaltliche Aenderung der Texte, nur geringerer Laufzeit-Overhead.

## Exakte Hook-Liste

### MU-Plugin

1. `stec_shortcode_atts`
2. `rest_pre_dispatch`
3. `rest_post_dispatch`
4. `save_post_stec_event`
5. `deleted_post`
6. `trashed_post`
7. `set_object_terms`
8. `wp_enqueue_scripts` (i18n-loader hotfix)

### Child-Theme

1. `gettext` (STEC CTA + Begriffsersetzung)
2. `gettext` (Woo Checkout/Thankyou Fallback)
3. `pre_load_script_translations` (STEC JS i18n)

## Warum keine Plugin-Core-Patches

1. Plugin-Updates wuerden Core-Edits ueberschreiben.
2. MU-Plugin + Child-Theme Hooks bleiben update-sicher.
3. Verhalten bleibt zentral dokumentiert und reproduzierbar.

## Cache-Design und Invalidation

1. Caching ist route-spezifisch (`/stec/vX/events`).
2. Response-Schema bleibt unveraendert.
3. Invalidation erfolgt ueber Version-Bump statt aggressivem Transient-Loeschen.
4. Alte Cache-Eintraege laufen nach kurzer TTL automatisch aus.

## Rollback

1. MU-Plugin deaktivieren:
   - `wp-content/mu-plugins/awz-stec-performance.php` umbenennen/entfernen.
2. i18n-loader Hotfix deaktivieren:
   - `wp-content/mu-plugins/awz-stec-i18n-loader-hotfix.php` umbenennen/entfernen.
3. Child-Theme Guards rueckgaengig machen:
   - letzte bekannten funktionierenden Dateien aus Git/Backup wiederherstellen.
4. Verifikation:
   - `/weiterbildung` laden
   - REST-Aufruf `stec/v5/events` pruefen
   - keine `stec-de_DE-*.json` 404 Requests mehr im Network
   - Buchungsflow auf Event-Seite testen

## Messung (Vorher/Nachher)

Checkliste: `tests/manual/performance-calendar.md`

Empfohlene Zielwerte:

1. Events-API p95 < 700ms
2. Events-Payload p95 < 250KB
3. Sichtbarer Kalender-Load < 2s Desktop / < 3s Mobile
