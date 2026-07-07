<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des tickets — {{ $usineName }}</title>
    <style>
        @page { margin: 18mm 14mm 20mm 14mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
            margin: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo { width: 70px; }
        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin: 8px 0 14px;
        }
        .meta {
            margin-bottom: 12px;
            line-height: 1.6;
        }
        .meta strong { font-size: 11px; }
        .tickets {
            width: 100%;
            border-collapse: collapse;
        }
        .tickets th,
        .tickets td {
            border: 1px solid #999;
            padding: 5px 6px;
        }
        .tickets th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .tickets td.num { text-align: right; }
        .total-row td {
            font-weight: bold;
            background: #fafafa;
        }
        .empty {
            text-align: center;
            color: #666;
            padding: 18px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            @if ($logoPath)
                <td style="width: 80px;">
                    <img src="{{ $logoPath }}" alt="Logo" class="logo">
                </td>
            @endif
            <td></td>
        </tr>
    </table>

    <div class="doc-title">LISTE DES TICKETS</div>

    <div class="meta">
        <div><strong>USINE :</strong> {{ $usineName }}</div>
        <div><strong>Période du :</strong> {{ $dateDebut }} au {{ $dateFin }}</div>
    </div>

    <table class="tickets">
        <thead>
            <tr>
                <th style="width: 14%;">Date Création</th>
                <th style="width: 14%;">Date Ticket</th>
                <th style="width: 14%;">Véhicule</th>
                <th style="width: 44%;">N° Ticket</th>
                <th style="width: 14%;">Poids (kg)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr>
                    <td>{{ $ticket['date_creation'] }}</td>
                    <td>{{ $ticket['date_ticket'] }}</td>
                    <td>{{ $ticket['vehicule'] }}</td>
                    <td>{{ $ticket['numero_ticket'] }}</td>
                    <td class="num">{{ $ticket['poids'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty">Aucun ticket trouvé pour cette période.</td>
                </tr>
            @endforelse
            @if (count($tickets) > 0)
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">TOTAL POIDS</td>
                    <td class="num">{{ $totalPoids }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
