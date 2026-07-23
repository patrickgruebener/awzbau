# Epic 006: Content und Konsolidierung

**Status:** Draft
**Erstellt:** 2026-07-22
**Priorität:** Medium
**Risk:** Low

> Risk-Begründung: Content- und Redirect-Arbeit, keine kritische Logik. Redirects sauber testen, damit keine Ranking-Signale verloren gehen.

---

## Background

Sammelepic für die inhaltlichen und strukturellen Maßnahmen aus dem SEO-Report. Baut idealerweise auf EPIC-004 auf, da die interne Verlinkung und die Trust-Signale im selben server-seitigen Block ausgegeben werden können.

---

## Tasks (mit Schätzung)

| Task | Inhalt | Solo-Dev (Judith) | Mit Claude |
|---|---|---|---|
| **T-01 Meister-URLs konsolidieren** | 9 konkurrierende URLs zum Thema Straßenbauermeister/Meisterprüfung. Pro Thema eine Zielseite bestimmen, Alt-Seiten per 301 bündeln. Beendet die interne Kannibalisierung. | 4–6 h | M |
| **T-02 Trust-Signale einbinden** | YouTube-Imagefilm, Presseartikel (Siegener Zeitung), Geschäftsführung mit Name und Rolle auf den Lehrgangsseiten sichtbar machen. Nutzt vorhandene Assets. | 3–6 h | S |
| **T-03 Alt-Texte & Thin-Content** | Rund ein Drittel der Bilder ohne Alt-Text nachziehen, dünne Seiten (`/karriere/`, `/zertifizierung/`, u. a.) anreichern | 6–10 h | M |

**Summe Epic 006:** Solo 13–22 h, mit Claude ~6–10 h aktive Zeit.

---

## Acceptance Criteria

- [ ] Pro Meister-Thema genau eine indexierbare Zielseite, Alt-URLs per 301 gebündelt
- [ ] Trust-Signale auf mindestens den Straßenbauermeister-Lehrgangsseiten sichtbar
- [ ] Alt-Text-Abdeckung auf Traffic-starken Seiten deutlich erhöht
- [ ] Priorisierte Thin-Content-Seiten auf mindestens 250 Wörter angereichert

---

## Ask Before Proceeding

- [ ] Vor Redirect-Aktivierung (T-01) Zielseiten-Auswahl mit Patrick bestätigen
