# Task 014: GSC-API-Anbindung awz-bau.de (optional)

**Status:** Draft
**Erstellt:** 2026-07-21
**Priorität:** Low
**Risk:** Low

> Risk-Begründung: Lesende API-Anbindung (`webmasters.readonly`), lokales Skript. Optional und nur nach separater Freigabe. Kein Blocker für den Report.

---

## Background

Für den einmaligen SEO-Report reicht ein manueller GSC-Export (zwei Minuten, keine Einrichtung). Eine API-Anbindung lohnt erst, wenn AWZ-SEO ein wiederkehrendes Monitoring werden soll, dann spiegelt sie das Muster des W.A.F.-Cockpits (regelmäßiger Refresh, Frühwarnung) und erlaubt Automatisierung. Dieser Task hält die Option sauber fest, ohne den Report zu blockieren.

---

## Approach

Kleines Python-Skript, das per Google Search Console API Query- und Seiten-Daten für awz-bau.de zieht und als CSV/JSON ablegt. Vor der Umsetzung Aufwand final schätzen und Patrick zur Freigabe vorlegen.

---

## Scope

### Voraussetzungen (vor Umsetzung klären)

- Welches Google-Konto besitzt awz-bau.de in der GSC? (bestimmt den OAuth-Aufwand)
- Search-Console-API im GCP-Projekt aktivieren
- OAuth-Client mit Scope `webmasters.readonly` + einmaliges Consent

### Erstellt (bei Umsetzung)

- Skript unter `seo-report-2026-07/tools/` oder eigenem Verzeichnis
- Credentials außerhalb Git (`.gitignore` beachten)

### Off-limits

- Kein Schreibzugriff (nur `readonly`)
- Keine Umsetzung ohne Effort-Gate-Freigabe

---

## Acceptance Criteria

- [ ] Effort final geschätzt und Patrick zur Freigabe vorgelegt
- [ ] Bei Freigabe: Skript zieht Query-/Seiten-Daten für awz-bau.de reproduzierbar
- [ ] Credentials sicher abgelegt, nicht in Git
- [ ] Bei zu hohem Aufwand: Task verworfen, manueller Export bleibt Standard

### Non-Goals

- Kein Dashboard, kein Alerting in diesem Task (separat, falls gewünscht)

---

## Ask Before Proceeding

- [ ] Umsetzung nur nach ausdrücklicher Freigabe (Effort-Gate).

---

## Related Docs

- `tasks/EPIC-003-seo-geo-report.md`, `tasks/011-seo-datenerhebung.md`
