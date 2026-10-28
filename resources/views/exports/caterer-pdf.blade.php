<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Dossier traiteur — {{ $event->name }}</title>
    <style>
        @page {
            margin: 16mm 14mm 20mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #2a1f17;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10pt;
            line-height: 1.45;
        }

        h1 {
            margin: 0;
            font-size: 22pt;
            font-weight: normal;
        }

        .eyebrow {
            margin: 0 0 3mm;
            color: #8a5a2b;
            font-size: 8pt;
            letter-spacing: 1.6pt;
            text-transform: uppercase;
        }

        .header {
            border-bottom: 0.6pt solid #d8cbb8;
            padding-bottom: 5mm;
        }

        .header p {
            margin: 1.5mm 0 0;
            color: #6b5b4b;
        }

        .meta {
            margin-top: 4mm;
            color: #6b5b4b;
            font-size: 8.5pt;
        }

        .summary {
            width: 100%;
            margin: 6mm 0;
            border-collapse: collapse;
        }

        .summary td {
            width: 25%;
            border: 0.6pt solid #d8cbb8;
            padding: 3mm;
            vertical-align: top;
        }

        .summary .label {
            color: #6b5b4b;
            font-size: 8pt;
            letter-spacing: 0.8pt;
            text-transform: uppercase;
        }

        .summary .value {
            margin-top: 1mm;
            color: #8a5a2b;
            font-size: 16pt;
        }

        h2 {
            margin: 7mm 0 2mm;
            font-size: 13pt;
            font-weight: normal;
        }

        table.households {
            width: 100%;
            border-collapse: collapse;
        }

        table.households th,
        table.households td {
            border-bottom: 0.5pt solid #e3d9ca;
            padding: 2.4mm 2mm;
            text-align: left;
            vertical-align: top;
        }

        table.households thead th {
            border-bottom: 0.8pt solid #8a5a2b;
            color: #6b5b4b;
            font-size: 8pt;
            letter-spacing: 0.8pt;
            text-transform: uppercase;
        }

        table.households tfoot th,
        table.households tfoot td {
            border-top: 0.8pt solid #8a5a2b;
            border-bottom: none;
            font-weight: bold;
        }

        .muted {
            color: #6b5b4b;
        }

        .empty {
            margin: 4mm 0;
            color: #6b5b4b;
            font-style: italic;
        }

        .footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            border-top: 0.5pt solid #d8cbb8;
            padding-top: 2mm;
            color: #6b5b4b;
            font-size: 7.5pt;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="eyebrow">Dossier traiteur</p>
        <h1>{{ $event->name }}</h1>
        <p>
            {{ $event->starts_at->translatedFormat('l j F Y') }} à {{ $event->starts_at->format('H\hi') }}
            — {{ $event->venue_name }}@if ($event->venue_address), {{ $event->venue_address }}@endif
        </p>
        <p class="meta">
            Document généré le {{ $generatedAt->translatedFormat('j F Y \à H\hi') }}.
            Couverts et régimes issus des réponses confirmées.
        </p>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Couverts confirmés</div>
                <div class="value">{{ $summary['coming'] }}</div>
            </td>
            <td>
                <div class="label">Foyers présents</div>
                <div class="value">{{ $summary['attending'] }}</div>
            </td>
            <td>
                <div class="label">Régimes signalés</div>
                <div class="value">{{ $summary['diet_households'] }}</div>
            </td>
            <td>
                <div class="label">Places prévues</div>
                <div class="value">{{ $summary['seats'] }}</div>
            </td>
        </tr>
    </table>

    <h2>Foyers présents</h2>

    @if ($households->isEmpty())
        <p class="empty">Aucun foyer n'a encore confirmé sa présence.</p>
    @else
        <table class="households">
            <thead>
                <tr>
                    <th style="width: 30%;">Foyer</th>
                    <th style="width: 10%;">Couverts</th>
                    <th style="width: 25%;">Prénoms</th>
                    <th style="width: 35%;">Régime / allergies</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($households as $household)
                    <tr>
                        <td>{{ $household['name'] }}</td>
                        <td>{{ $household['seats'] }}</td>
                        <td class="muted">{{ $household['attendees'] ?: '—' }}</td>
                        <td class="muted">{{ $household['dietary'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th>{{ $summary['coming'] }}</th>
                    <td colspan="2" class="muted">{{ $summary['diet_households'] }} foyer{{ $summary['diet_households'] > 1 ? 's' : '' }} avec régime signalé</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">
        Document confidentiel réservé au prestataire — Hub Événement, Abidjan.
        Aucune coordonnée d'invité n'est incluse.
    </div>
</body>
</html>
