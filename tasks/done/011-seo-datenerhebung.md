# Task 011: SEO-Datenerhebung & Baseline-Audit

**Status:** Done
**Erstellt:** 2026-07-21
**Abgeschlossen:** 2026-07-21
**Priorität:** High
**Risk:** Low

> Risk-Begründung: Lesender Live-Crawl mit eingebautem Rate-Limit (5 parallel, 1s Delay, robots.txt-konform) plus Einarbeitung von GSC-Exporten. Kein Schreibzugriff auf die Website.

---

## Background

Grundlage für den SEO-/GEO-Report (EPIC-003). Es braucht einen belastbaren Ist-Stand von awz-bau.de: technische SEO-Basis, Content-/GEO-Signale und echte Ranking-Daten für die Lehrgänge, allen voran Straßenbauermeister Teil I und II. Die EPIC-002-Hypothesen (fehlende Yoast-Konfiguration für `stec_event`, kannibalisierende Ticket-Produkte, Canonical-Duplikate) werden hier mit Daten geprüft.

---

## Approach

1. Kostenlose lokale SEO-Skill-Suite gegen die Live-Site laufen lassen, Outputs sichern.
2. Straßenbauermeister-Lehrgangsseite gezielt tief analysieren.
3. GSC-Exporte von Patrick einarbeiten (Ranking-Schicht).
4. Gegen die EPIC-002/Task-009-Hypothesen prüfen und Rohbefunde strukturiert ablegen.

---

## Scope

### Was ausgeführt / erstellt wird

- `/seo audit https://awz-bau.de` → `seo-report-2026-07/audit/`
- `/seo geo https://awz-bau.de` → `seo-report-2026-07/geo/`
- `/seo page <straßenbauermeister-lehrgang-url>` → `seo-report-2026-07/page-strassenbauermeister/`
- `seo-report-2026-07/gsc-auswertung.md` – aufbereitete GSC-Daten
- `seo-report-2026-07/rohbefunde.md` – strukturierte Zusammenfassung + Hypothesen-Check

### Off-limits

- Kein Schreibzugriff auf awz-bau.de
- Kein W.A.F.-Sistrix/Cockpit

---

## Acceptance Criteria

### Funktional

- [x] `/seo audit` durchgelaufen, Health Score und ACTION-PLAN liegen im Arbeitsverzeichnis (`audit/FULL-AUDIT-REPORT.md`, `audit/ACTION-PLAN.md`, Health Score 49/100 als oberer Richtwert — siehe Korrekturhinweis im Report zur JS-Rendering-Fehlbewertung)
- [x] `/seo geo` durchgelaufen, GEO Readiness Score liegt vor (`geo/GEO-ANALYSIS.md`, 31/100)
- [x] Straßenbauermeister-Seite deep-analysiert (Slug via Crawl/Sitemap ermittelt) (`page-strassenbauermeister/PAGE-ANALYSIS.md`, 3 Kernseiten)
- [x] GSC-Exporte eingearbeitet: echte Position, Impressionen, Klicks für „straßenbauermeister"/„meister" dokumentiert; Indexierungsstatus der Lehrgangsseiten notiert (`rohbefunde.md` Abschnitt 2+3)
- [x] Hypothesen-Check dokumentiert: Title/Meta/Canonical der Lehrgangsseiten, `stec_event` in XML-Sitemap ja/nein, Ticket-Produkte indexierbar ja/nein, Canonical-Duplikate ja/nein (`rohbefunde.md` Abschnitt 7+8)

**Wichtigster Befund (Abweichung von der EPIC-002-Ausgangshypothese):** Kursinhalt fehlt im Server-HTML aller 24 Lehrgangsseiten (JS-Rendering-Lücke, STEC v5 lädt Content per REST-API nach) — das ist die wahrscheinlich wichtigere Ursache als die ursprünglich vermutete fehlende Yoast-Konfiguration. Details: `rohbefunde.md` Abschnitt 1 + 4a.

### Non-Goals

- Keine Fixes, keine Redaktion des Reports (Task 013)
- Kein Wettbewerbs-Benchmark (Task 012)

---

## Technical Notes

- SEO-Skills unter `/Users/patrick/.agents/skills/` (Orchestrator `seo` + Sub-Skills). Live-Crawl kostenlos, keine Paid-Tools.
- GSC hat kein Auto-Tool im System. Patrick liefert zwei Exporte (letzte 3 Monate): (a) Suchanfragen gefiltert auf „straßenbauermeister"/„meister", (b) Top-Seiten der Lehrgangs-/Kurs-URLs.
- Lehrgänge liegen als STEC-Events unter `/lehrgang/[slug]/`, Liste unter `/weiterbildung` (siehe `CLAUDE.md`).

### Abhängigkeiten

- **Blocker für Abschluss:** GSC-Exporte von Patrick. Bis dahin läuft der Task auf Crawl-Basis vor, Ranking-Schicht wird nachgezogen.

---

## Verification

- Alle drei Skill-Läufe haben Output-Dateien erzeugt (Scores vorhanden).
- `rohbefunde.md` beantwortet für jede EPIC-002-Hypothese ja/nein mit Beleg (Crawl-Befund oder GSC-Zahl).
- Für Straßenbauermeister ist die tatsächliche Google-Position dokumentiert.

---

## Ask Before Proceeding

- Keine – Task kann selbstständig laufen. Nur der Abschluss wartet auf die GSC-Exporte.

---

## Related Docs

- `tasks/EPIC-003-seo-geo-report.md`
- `tasks/EPIC-002-seo-event-indexing.md`, `tasks/009-awz-search-console-indexierung.md`
- `CLAUDE.md`
