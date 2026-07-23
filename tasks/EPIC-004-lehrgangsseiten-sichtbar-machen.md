# Epic 004: Lehrgangsseiten sichtbar machen (SSR, Schema, Struktur)

**Status:** Draft
**Erstellt:** 2026-07-22
**Priorität:** Critical
**Risk:** Medium

> Risk-Begründung: Greift ins Rendering der buchbaren Kernseiten ein (Child-Theme-Hook, kein Plugin-Eingriff). Booking-Flow darf nicht brechen. Kein DB-Schreibzugriff.

---

## Background

Kernbefund des SEO-Reports (Juli 2026): Auf allen 24 Lehrgangsseiten (`/lehrgang/[slug]/`) fehlt der Kursinhalt im Server-HTML. STEC v5 gibt in `includes/shortcodes/shortcode.stec_single.php:159` einen leeren Container aus (`<div class="stec-single-page"></div>`) und füllt ihn per JavaScript über die REST-API. Google sieht den Inhalt nur verzögert, KI-Crawler (ChatGPT, Perplexity) gar nicht.

**Machbarkeit geklärt (2026-07-22): vom Child-Theme aus lösbar, ohne Plugin-Änderung.** Die Beschreibung existiert vollständig serverseitig. `class.rest-stec_event-controller.php:819` zeigt: `$data['description'] = apply_filters('the_content', $data['content']['raw'])`. Der Kurstext ist also der Post-Inhalt des Events und im Seed reich vorhanden (inkl. `<h2>`-Struktur).

---

## Approach

Neuer Hook im Child-Theme (`inc/awz-seo.php`, per `require_once` in `functions.php`), der auf `stec_single_after_content` läuft:

1. `event_id` aus dem übergebenen `$shortcode_atts` lesen.
2. `Events::get_rest_event(array('id' => $event_id, 'start_date' => ..., 'context' => 'event', 'permission_type' => 'read_permission', 'include_ro_data' => 1))` aufrufen (dieselbe Funktion, die STEC intern in `prefetch_event` nutzt).
3. Serverseitig ausgeben: H1 = Event-Titel, darunter `description`-HTML (bringt die vorhandenen H2 mit), plus Eckdaten (Termine, Ort, Preis aus dem verknüpften WooCommerce-Produkt).
4. Aus denselben Daten ein `Course` / `EducationEvent` JSON-LD erzeugen.
5. Verwandte Lehrgänge server-seitig verlinken (interne Verlinkung, entschärft die Kannibalisierung).

**Wichtig:** Der Block landet als Geschwister-Element hinter `#stec-single-1` (via `after_content`-Hook), React mountet nur in den leeren Container davor und überschreibt den SSR-Block nicht. Gegen sichtbare Dopplung: SSR-Block nach der React-Hydration per CSS/JS visuell ausblenden. Inhaltlich äquivalent, also kein Cloaking.

---

## Tasks (mit Schätzung)

| Task | Inhalt | Solo-Dev (Judith) | Mit Claude |
|---|---|---|---|
| **T-01 SSR-Fallback** | Hook `stec_single_after_content`, `get_rest_event`, H1 + Beschreibung + Eckdaten serverseitig rendern, über alle Event-Typen (Einzel, Serie, Umbrella) testen | 12–16 h | M |
| **T-02 Course-Schema** | `Course`/`EducationEvent` JSON-LD aus denselben Daten, Rich-Results-Test | 4–6 h | S |
| **T-03 Meta-Description + Title-Länge** | Yoast: Auto-Description aus dem jetzt server-seitigen Content, Title-Template auf 50–60 Zeichen | 3–5 h | S |
| **T-04 Interne Verlinkung** | „Verwandte Lehrgänge"-Block serverseitig im selben Hook | 3–5 h | S–M |

**Summe Epic 004:** Solo 22–32 h, mit Claude ~7–12 h aktive Zeit.

---

## Acceptance Criteria

- [ ] Server-HTML einer `/lehrgang/`-Seite enthält Kurstitel (H1), Beschreibung (mit H2) und Eckdaten ohne JavaScript
- [ ] `Course`/`EducationEvent` JSON-LD besteht den Google Rich Results Test
- [ ] Yoast liefert Title unter 60 Zeichen und eine gefüllte Meta-Description
- [ ] Booking-Flow (TC-01 bis TC-04 in `tests/manual/booking-flow.md`) unverändert grün
- [ ] Keine sichtbare Inhalts-Dopplung für Nutzer nach React-Hydration
- [ ] PHP-Syntax-Check grün, kein Debug-Output

---

## Technical Notes

- Ziel-Hook: `do_action('stec_single_after_content', $shortcode_atts)` in `shortcode.stec_single.php:160`. `$shortcode_atts` enthält `event_id` und `id`.
- Datenquelle: `Stachethemes\..\Events::get_rest_event()` (Klasse `includes/class.events.php`), Feld `description` = Post-Content via `the_content`.
- Termine/Preis: WooCommerce-Ticket-Produkt ist per STEC verknüpft (siehe `CLAUDE.md`, Booking-Repair). Preis/Termin daraus ziehen.
- Ablageort: `inc/awz-seo.php` (in EPIC-002 vorgesehen, existiert noch nicht, hier neu anlegen).

---

## Ask Before Proceeding

- [ ] FTP-Deploy nur nach bestandenem lokalem Test (Docker, localhost:8080)
