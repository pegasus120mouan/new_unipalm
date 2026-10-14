<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des plantations - Unipalm</title>
    <style>
        @page { margin: 10mm 8mm 12mm 8mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #222;
            margin: 0;
        }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .logo { width: 48px; }
        .company-name { font-size: 14px; font-weight: bold; color: #008000; margin: 0; }
        .company-subtitle { font-size: 9px; color: #666; margin: 2px 0 0; }
        .doc-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin: 6px 0 8px;
            text-transform: uppercase;
        }
        .meta { margin-bottom: 8px; color: #444; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th,
        table.data td {
            border: 1px solid #444;
            padding: 3px 4px;
            vertical-align: top;
        }
        table.data th {
            background: #111;
            color: #fff;
            font-weight: bold;
            text-align: left;
            font-size: 7px;
            text-transform: uppercase;
        }
        .text-right { text-align: right; }
        .empty { text-align: center; font-style: italic; color: #666; padding: 12px; }
        .footer-note { margin-top: 8px; text-align: center; font-size: 7px; color: #666; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            @if ($logoPath)
                <td style="width: 60px;">
                    <img src="{{ $logoPath }}" alt="Logo" class="logo">
                </td>
            @endif
            <td>
                <p class="company-name">UNIPALM COOP</p>
                <p class="company-subtitle">Liste des plantations</p>
            </td>
        </tr>
    </table>

    <div class="doc-title">Liste des plantations</div>

    <div class="meta">
        {{ number_format($total, 0, ',', ' ') }} plantation(s)
        · {{ number_format($nbPlanteurs, 0, ',', ' ') }} planteur(s)
        · Superficie {{ number_format((float) $superficieTotale, 2, ',', ' ') }} ha
        · Parcelles tracées {{ number_format($parcellesTracees, 0, ',', ' ') }} / {{ number_format($total, 0, ',', ' ') }}
        <br>
        Édité le {{ $generatedAt->format('d/m/Y à H:i') }}
        @if ($filters['q'] !== '' || $filters['region'] !== '' || $filters['village'] !== '')
            · Filtres :
            @if ($filters['q'] !== '') recherche « {{ $filters['q'] }} » @endif
            @if ($filters['region'] !== '') région « {{ $filters['region'] }} » @endif
            @if ($filters['village'] !== '') village « {{ $filters['village'] }} » @endif
        @else
            · Toutes les plantations
        @endif
    </div>

    @if ($total === 0)
        <p class="empty">Aucune plantation à exporter.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $index => $cell)
                            <td @class(['text-right' => in_array($index, [5, 6], true)])>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-note">UNIPALM COOP — document généré automatiquement</div>
</body>
</html>
