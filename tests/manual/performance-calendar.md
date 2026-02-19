# Checkliste: Performance Kalender `/weiterbildung`

Diese Checkliste dient fuer Vorher/Nachher-Messungen der Kalender-Performance.

## Testumgebung

1. URL: `/weiterbildung`
2. Endpoint: `/wp-json/stec/v5/events`
3. Browser: Chrome (Inkognito, ohne Extensions)
4. Netzwerkprofil:
   - Desktop: ohne Drosselung
   - Optional Mobile: Fast 4G

## Messmatrix

Jeweils 20 Messungen pro Szenario.

1. Seite `/weiterbildung` frisch laden
2. REST-Endpoint mit typischen Query-Parametern aufrufen

## API-Messung mit curl (Beispiel)

```bash
BASE_URL="http://localhost:8080"
URL="$BASE_URL/wp-json/stec/v5/events?context=event&per_page=20&page=1&lang=de"

for i in $(seq 1 20); do
  curl -sS -o /tmp/stec-events.json \
    -w "run=$i code=%{http_code} ttfb=%{time_starttransfer} total=%{time_total} bytes=%{size_download}\n" \
    "$URL"
done
```

## Zu protokollierende Werte

1. p50 und p95 `time_starttransfer` (TTFB)
2. p50 und p95 `size_download` (Payload)
3. Anteil Cache-Hits (`X-AWZ-STEC-Cache: HIT`)
4. Wahrgenommene Zeit bis Kalender sichtbar
5. Anzahl `stec-de_DE-*.json` `404` Requests (sollte `0` sein)

## Zielwerte

1. Events-API p95 < 700ms
2. Events-Payload p95 < 250KB
3. Sichtbarer Kalender-Load < 2s Desktop / < 3s Mobile

## Funktionale Regression parallel pruefen

1. Event-Kacheln/Liste vollstaendig
2. Monatsnavigation funktioniert
3. Event-Detailnavigation funktioniert
4. Buchungsflow funktioniert
5. Browser-Konsole ohne `Failed to fetch i18n data` fuer STEC

## Ergebnisvorlage

```text
Datum:
Umgebung:
Plugin-Version:
PHP-Version:

Vorher:
- API TTFB p50 / p95:
- API Payload p50 / p95:
- Kalender sichtbar nach:

Nachher:
- API TTFB p50 / p95:
- API Payload p50 / p95:
- Kalender sichtbar nach:

Regressionen:
- Ja/Nein (+ Details)
```
