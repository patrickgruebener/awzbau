# Epic 003: SEO- & GEO-Report für AWZ Bau

**Status:** Draft
**Erstellt:** 2026-07-21
**Priorität:** High
**Risk:** Low

> Risk-Begründung: Reine Analyse- und Redaktionsarbeit plus Live-Crawl (lesend, rate-limited). Kein Schreibzugriff auf awz-bau.de, keine Code-Änderungen. Die Umsetzung der abgeleiteten Fixes läuft über EPIC-002/Task 009, nicht über dieses Epic.

---

## Background

Das AWZ hat um eine SEO-Standortbestimmung gebeten. Konkreter Auslöser: Judith Hamers ist aufgefallen, dass der Lehrgang „Straßenbauermeister*in Teil I und II" nicht (oder nicht oben) in Google erscheint. Ziel ist ein aussagekräftiger SEO- und GEO-Report als PDF mit priorisierten, umsetzbaren Maßnahmen, Fokus auf die Sichtbarkeit der Lehrgänge.

Wichtige Vorentscheidung: Der Sistrix-Zugang der W.A.F. wird NICHT genutzt (fremdes Mandat, fremde Lizenz, awz-bau.de dort nicht angelegt). Datengrundlage sind stattdessen AWZ's eigene Google Search Console (voller Zugang vorhanden) plus die kostenlose lokale SEO-Skill-Suite (Live-Crawl) und freie SERP-Recherche.

Bereits vorhandene, noch nicht umgesetzte Vorarbeit im Repo, die der Report mit echten Daten bestätigt/widerlegt:
- **EPIC-002** (Draft): `stec_event` nicht in Yoast konfiguriert → Lehrgänge ohne sauberen Title/Meta/Canonical, fehlen in Sitemap; Ticket-Produkte kannibalisieren Lehrgangsseiten. Wahrscheinliche Hauptursache.
- **Task 009** (Draft): GSC Duplikat-Canonical-Warnungen März 2026.

---

## Approach

Report-Erstellung in drei aufeinander aufbauenden Tasks plus einem optionalen API-Task. GSC-Daten werden von Patrick manuell zugeliefert (schnellster Weg), daher ist die Arbeit über Sessions verteilt.

- **Task 011** – Datenerhebung & Baseline-Audit (Skill-Läufe + GSC-Export einarbeiten)
- **Task 012** – Wettbewerbs-Benchmark Lehrgänge (freie SERP-Recherche)
- **Task 013** – Report-Synthese, PDF & Auslieferung
- **Task 014** (optional, separat freizugeben) – GSC-API-Anbindung für wiederkehrendes Monitoring

Arbeitsverzeichnis für Roh-Outputs und Report-Markdown: `seo-report-2026-07/` im Repo-Root.

---

## Scope

### Dateien / Artefakte, die erstellt werden

- `seo-report-2026-07/` – Roh-Outputs der SEO-Skills, GSC-Auswertung, Benchmark, Report-Markdown
- Google Doc + PDF-Export in Drive (finaler Deliverable)
- Task-Dateien 011–014 in `tasks/`

### Was NICHT passiert (off-limits)

- Kein Schreibzugriff auf awz-bau.de (kein FTP, keine DB, keine WP-Änderung)
- Keine Nutzung des W.A.F.-Sistrix/Cockpit-Zugangs
- Kein Direktversand an AWZ ohne Patricks Freigabe

---

## Acceptance Criteria

- [ ] SEO- und GEO-Baseline erhoben (Health Score + GEO Score liegen vor)
- [ ] GSC-Exporte eingearbeitet, echte Positionen für Straßenbauermeister dokumentiert
- [ ] Wettbewerbs-Benchmark für die Kern-Lehrgänge erstellt
- [ ] Entscheider-Report mit Technik-Anhang geschrieben, jede Kernaussage belegt
- [ ] Writing-Style-Audit gegen `anti-ai-writing-style.md` bestanden
- [ ] PDF in Drive abgelegt, Link an Patrick, sauber lesbar
- [ ] Maßnahmen den bestehenden EPIC-002/Task 009 zugeordnet bzw. neue Tasks vorgeschlagen

### Non-Goals

- Umsetzung der SEO-Fixes im Code (läuft über EPIC-002/Task 009)
- GSC-API-Anbindung (optional, Task 014)

---

## Related Docs

- `tasks/011-seo-datenerhebung.md`, `tasks/012-seo-wettbewerbs-benchmark.md`, `tasks/013-seo-report-synthese-pdf.md`, `tasks/014-gsc-api-anbindung.md`
- `tasks/EPIC-002-seo-event-indexing.md` – Umsetzungs-Track der wahrscheinlichen Hauptursache
- `tasks/009-awz-search-console-indexierung.md` – GSC Duplikat-Warnungen
- `CLAUDE.md` – Architektur (STEC v5, Yoast, `/lehrgang/[slug]/`, `/weiterbildung`)
