# Task 013: Report-Synthese, PDF & Auslieferung

**Status:** Draft
**Erstellt:** 2026-07-21
**Priorität:** High
**Risk:** Low

> Risk-Begründung: Redaktion + PDF-Erzeugung. Einziger sensibler Punkt ist die Auslieferung an AWZ, die nur nach Patricks Freigabe erfolgt.

---

## Background

Abschluss des SEO-/GEO-Reports (EPIC-003). Die Befunde aus Task 011 (Baseline + GSC) und Task 012 (Benchmark) werden zu einem Entscheider-Report mit Technik-Anhang verdichtet und als PDF ausgeliefert. Zielleserin ist Judith Hamers (Bildungsprojektleiterin, nicht technisch).

---

## Approach

1. Report-Markdown nach fester Struktur schreiben (Entscheider vorne, Technik hinten).
2. Writing-Style-Audit gegen `anti-ai-writing-style.md`.
3. Markdown → Google Doc → PDF-Export in Drive.
4. Maßnahmen den bestehenden Code-Tasks zuordnen.

---

## Scope

### Report-Struktur

1. Executive Summary (Status in ~5 Sätzen, für Judith)
2. Sichtbarkeits-Status (GSC-Kennzahlen, Fokus Lehrgänge/Straßenbauermeister)
3. Warum der Straßenbauermeister-Lehrgang nicht rankt (Ursachen aus Daten + Benchmark)
4. Wettbewerbs-Benchmark (kompakt)
5. GEO-Status (Sichtbarkeit in AI-Suche, was AWZ zitierfähig macht)
6. Priorisierte Maßnahmen (Critical/High/Medium, je Wirkung + Aufwand)
7. Technischer Anhang (Scores, Detailbefunde, konkrete WordPress/Yoast-Schritte)

### Erstellt

- `seo-report-2026-07/report.md`
- Google Doc + PDF in Drive
- Ordner/Ablage-Link an Patrick

### Off-limits

- Kein Direktversand an AWZ ohne Patricks Freigabe

---

## Acceptance Criteria

### Funktional

- [ ] Report deckt alle 7 Abschnitte ab
- [ ] Jede Kernaussage ist belegt (GSC-Zahl, Crawl-Befund oder Benchmark-Vergleich)
- [ ] Executive Summary ist ohne Fachjargon lesbar
- [ ] Technik-Anhang enthält umsetzbare WordPress/Yoast-Schritte
- [ ] Maßnahmen den bestehenden EPIC-002/Task 009 zugeordnet bzw. neue Tasks vorgeschlagen

### Redaktion / Qualität

- [ ] Writing-Style-Audit gegen `~/Documents/memory/prompts/anti-ai-writing-style.md` bestanden (keine Negativ-Parallelismen, keine KI-Floskeln, keine Gedankenstriche)
- [ ] Deutsche Umlaute und Anführungszeichen durchgehend

### Deliverable

- [ ] PDF in Drive abgelegt, öffnet sauber, Link an Patrick

### Non-Goals

- Keine Code-Umsetzung der Fixes
- Kein Versand an AWZ ohne Freigabe

---

## Technical Notes

- PDF-Weg: `import_to_google_doc`/`create_doc` (Markdown → Google Doc), dann `export_doc_to_pdf`. Ablage in Drive.
- Als externer Client-Deliverable gilt das Writing Style Profile (Self-Audit-Pflicht ab ~80 Wörtern).

### Abhängigkeiten

- Task 011 (Baseline + GSC) und Task 012 (Benchmark) müssen abgeschlossen sein.

---

## Verification

- PDF geöffnet, Layout und Lesbarkeit geprüft.
- Stichprobe: drei Kernaussagen gegen ihre Belege in `seo-report-2026-07/` gegengecheckt.
- Drive-Link funktioniert.

---

## Ask Before Proceeding

- [ ] Vor jedem Versand oder jeder Freigabe an AWZ: Patricks ausdrückliche Bestätigung.

---

## Related Docs

- `tasks/EPIC-003-seo-geo-report.md`, `tasks/011-seo-datenerhebung.md`, `tasks/012-seo-wettbewerbs-benchmark.md`
- `tasks/EPIC-002-seo-event-indexing.md`, `tasks/009-awz-search-console-indexierung.md`
