# Task 013: Report-Synthese, PDF & Auslieferung

**Status:** Done
**Erstellt:** 2026-07-21
**Abgeschlossen:** 2026-07-23
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

- [x] Report deckt die Abschnitte 1-6 ab (Abschnitt 7, Technischer Anhang, auf Patricks Wunsch entfernt — er ist selbst Entwickler/IT für AWZ, kein separater technischer Adressat nötig)
- [x] Jede Kernaussage ist belegt (GSC-Zahl, Crawl-Befund oder Benchmark-Vergleich)
- [x] Executive Summary ist ohne Fachjargon lesbar
- [x] Ursachen-Einordnung ergänzt (Abschnitt 3.5): STEC-Update Februar 2026 als technische Wurzel, mit GSC-Vorher-Nachher-Beleg
- [x] Maßnahmen priorisiert und in EPIC-004/005/006 überführt (ersetzt die ursprünglich vorgesehene reine Zuordnung zu EPIC-002/Task 009)

### Redaktion / Qualität

- [x] Writing-Style-Audit gegen `~/Documents/memory/prompts/anti-ai-writing-style.md` bestanden (ein Negativ-Parallelismus in Abschnitt 4 gefunden und korrigiert)
- [x] Deutsche Umlaute und Anführungszeichen durchgehend

### Deliverable

- [x] PDF im MOVA-Brand-Template (Kopie von Patricks Google-Doc-Vorlage, nicht Markdown-Import) in Drive-Ordner `17qoIujnbtL_5AwaZo3UeG4MuTEQ1yLO6` abgelegt
- [x] Als Anhang direkt an Judith Hamers gesendet (Patrick hat nach Review selbst freigegeben und verschickt, 2026-07-23)

### Non-Goals

- Keine Code-Umsetzung der Fixes (folgt in EPIC-004/005, abhängig von Judiths Entscheidung)

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
