# Task 012: Wettbewerbs-Benchmark Lehrgänge

**Status:** Draft
**Erstellt:** 2026-07-21
**Priorität:** High
**Risk:** Low

> Risk-Begründung: Reine Recherche, freie SERP-Analyse, keine Paid-Tools, kein Zugriff auf awz-bau.de.

---

## Background

Teil des SEO-/GEO-Reports (EPIC-003). Judiths Kernfrage („warum rankt der Straßenbauermeister-Lehrgang nicht?") wird am direktesten durch den Vergleich mit den Seiten beantwortet, die aktuell auf Seite 1 stehen. Der Benchmark zeigt konkret, was die Ranker besser machen und was AWZ fehlt.

---

## Approach

1. Für die Kern-Lehrgänge die relevanten Suchbegriffe abfragen (freie SERP-Recherche).
2. Pro Begriff die Seite-1-Wettbewerber erfassen und knapp gegenüberstellen.
3. Konkrete Ableitungen für AWZ formulieren.

---

## Scope

### Suchbegriffe (Start-Set, bei Bedarf erweitern)

- „Straßenbauermeister", „Straßenbauermeister Teil I", „Straßenbauermeister Teil II", „Meister Straßenbau"
- 2–3 weitere Kern-Lehrgänge des AWZ (aus dem Crawl in Task 011 ableiten)
- Relevante regionale Varianten (z. B. mit Region/Bundesland), sofern die Lehrgänge überregional buchbar sind

### Erstellt

- `seo-report-2026-07/wettbewerbs-benchmark.md`

### Off-limits

- Kein Paid-Tool, keine Sistrix-Nutzung

---

## Acceptance Criteria

### Funktional

- [ ] Pro Kern-Lehrgang 3–5 Seite-1-Wettbewerber erfasst
- [ ] Gegenüberstellung je Wettbewerber: Seitentiefe/Content, Schema (EducationEvent/Course), interne Verlinkung, GEO-Signale
- [ ] Klar benannt, was die Ranker besser machen und was AWZ konkret fehlt
- [ ] Straßenbauermeister explizit abgedeckt (Judiths Fall)

### Non-Goals

- Keine Umsetzung, keine Report-Redaktion (Task 013)
- Keine erschöpfende Marktanalyse, bewusst kompakt

---

## Technical Notes

- Für die Wettbewerber-Seitenanalyse kann `/seo page <url>` genutzt werden, um Content-Tiefe/Schema objektiv zu vergleichen.
- Fokus auf Bildungsträger und Handwerkskammern/Meisterschulen, die für diese Lehrgänge ranken.

### Abhängigkeiten

- Baut auf dem Crawl aus Task 011 auf (Liste der AWZ-Kern-Lehrgänge).

---

## Verification

- `wettbewerbs-benchmark.md` enthält je Kern-Lehrgang eine Vergleichstabelle plus Ableitungen.
- Jede Ableitung ist an einem beobachteten Wettbewerber-Merkmal belegt, keine Vermutung.

---

## Ask Before Proceeding

- Keine – Task kann selbstständig laufen.

---

## Related Docs

- `tasks/EPIC-003-seo-geo-report.md`, `tasks/011-seo-datenerhebung.md`
