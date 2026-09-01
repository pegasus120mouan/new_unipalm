<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats de la recherche — tickets</title>
    <style>
        @page { margin: 12mm 10mm 14mm 10mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            color: #222;
            margin: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo { width: 58px; }
        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            margin: 4px 0 10px;
        }
        .meta {
            margin-bottom: 10px;
            line-height: 1.55;
        }
        .meta strong { font-size: 9px; }
        .tickets {
            width: 100%;
            border-collapse: collapse;
        }
        .tickets th,
        .tickets td {
            border: 1px solid #999;
            padding: 4px 5px;
        }
        .tickets th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .tickets td.num { text-align: right; }
        .tickets td.center { text-align: center; }
        .total-row td {
            font-weight: bold;
            background: #fafafa;
        }
        .empty {
            text-align: center;
            color: #666;
            padding: 16px;
        }
        .printed {
            margin-top: 8px;
            color: #666;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            @if ($logoPath)
                <td style="width: 70px;">
                    <img src="{{ $logoPath }}" alt="Logo" class="logo">
                </td>
            @endif
            <td></td>
        </tr>
    </table>

    <div class="doc-title">RÉSULTATS DE LA RECHERCHE — TICKETS</div>

    <div class="meta">
        @foreach ($criteria as $line)
            <div><strong>{{ $line }}</strong></div>
        @endforeach
        <div><strong>Période de la recherche :</strong> {{ $periode }}</div>
        <div><strong>Nombre de tickets :</strong> {{ $totalTickets }}</div>
    </div>

    <table class="tickets">
        <thead>
            <tr>
                <th style="width: 10%;">Date réception</th>
                <th style="width: 10%;">Date ticket</th>
                <th style="width: 16%;">N° Ticket</th>
                <th style="width: 14%;">Usine</th>
                <th style="width: 8%;">Poids</th>
                <th style="width: 8%;">Prix unit.</th>
                <th style="width: 14%;">Nom agent</th>
                <th style="width: 12%;">Pont</th>
                <th style="width: 8%;">Véhicule</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr>
                    <td class="center">{{ $ticket['date_reception'] }}</td>
                    <td class="center">{{ $ticket['date_ticket'] }}</td>
                    <td>{{ $ticket['numero_ticket'] }}</td>
                    <td>{{ $ticket['usine'] }}</td>
                    <td class="num">{{ $ticket['poids'] }}</td>
                    <td class="num">{{ $ticket['prix_unitaire'] }}</td>
                    <td>{{ $ticket['agent'] }}</td>
                    <td>{{ $ticket['pont'] }}</td>
                    <td>{{ $ticket['vehicule'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">Aucun ticket ne correspond aux critères de recherche.</td>
                </tr>
            @endforelse
            @if (count($tickets) > 0)
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">TOTAL ({{ $totalTickets }} ticket(s))</td>
                    <td class="num">{{ $totalPoids }}</td>
                    <td colspan="4"></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="printed">Imprimé le {{ $printedAt }}</div>
</body>
</html>
