# Epic 005: Technische SEO-Hygiene

**Status:** Draft
**Erstellt:** 2026-07-22
**Priorität:** High
**Risk:** Low

> Risk-Begründung: Kleine, gut abgegrenzte Template-, Config- und Server-Fixes. Kein Eingriff in den Booking-Flow.

---

## Background

Sammelepic für die technischen Einzelbefunde aus dem SEO-Report, die unabhängig vom SSR-Kernproblem (EPIC-004) umsetzbar sind. Alle mit konkretem Beleg im Report und Action-Plan.

---

## Tasks (mit Schätzung)

| Task | Inhalt | Solo-Dev (Judith) | Mit Claude |
|---|---|---|---|
| **T-01 Canonical-Template-Fix** | Rund ein Drittel der Lehrgangsseiten hat fehlerhafte Canonicals (Ziel 404 bzw. noindex-Produktseite). Per `wpseo_canonical`-Filter für `stec_event` self-referencing erzwingen. Behebt die GSC-Duplikat-Warnungen (Task 009). | 3–5 h | S |
| **T-02 Tote URLs & Redirects** | Tote Sitemap-URLs (mehrere `/lehrgang/`-404) bereinigen, 302→301 bei `/buchung-2/`, `/shop/` aus Sitemap, Dank-Seiten `/herzlichen-dank(-2)/` dedupen | 3–5 h | S |
| **T-03 Homepage-H1 & Multi-H1** | H1 „Beitragsnavigation" durch echten Titel ersetzen, doppeltes Navigations-H1 sitebreit im Theme fixen | 2–3 h | S |
| **T-04 Security-Header & robots.txt** | X-Content-Type-Options, X-Frame-Options, Referrer-Policy, HSTS via `.htaccess`; `X-Powered-By` abschalten; `Sitemap:`-Zeile in robots.txt | 2–3 h | S |

**Summe Epic 005:** Solo 10–16 h, mit Claude ~4–6 h aktive Zeit.

---

## Acceptance Criteria

- [ ] Stichprobe von 24 Lehrgangsseiten: alle mit self-referencing Canonical
- [ ] Keine 404 mehr in `stec_event-sitemap.xml`, `/shop/` nicht mehr gelistet
- [ ] Startseite hat genau eine echte, keyword-tragende H1
- [ ] Security-Header sitebreit gesetzt, `X-Powered-By` weg, `Sitemap:`-Zeile in robots.txt
- [ ] PHP-Syntax-Check grün

---

## Ask Before Proceeding

- [ ] FTP-Deploy und `.htaccess`-Änderung nur nach lokalem Test
- [ ] CSP bewusst zunächst nur im Report-Only-Modus (WooCommerce-Scripts)
