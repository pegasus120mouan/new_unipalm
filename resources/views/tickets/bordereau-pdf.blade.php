<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau de déchargement — {{ $agentName }}</title>
    <style>
        @page { margin: 18mm 14mm 22mm 14mm; }
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
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #008000;
            margin: 0;
        }
        .company-subtitle {
            font-size: 11px;
            color: #90ee90;
            margin: 4px 0 0;
        }
        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin: 10px 0 14px;
        }
        .meta {
            margin-bottom: 14px;
            line-height: 1.7;
        }
        .meta strong { font-size: 11px; }
        .usine-title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin: 14px 0 6px;
        }
        .tickets {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
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
        .subtotal td,
        .total td {
            background: #f0f0f0;
            font-weight: bold;
        }
        .total td {
            background: #e8e8e8;
        }
        .signature {
            margin-top: 28px;
            text-align: right;
            line-height: 1.8;
        }
        .empty {
            text-align: center;
            color: #666;
            padding: 20px;
            border: 1px solid #ccc;
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
            <td>
                <p class="company-name">UNIPALM COOP - CA</p>
                <p class="company-subtitle">Société Coopérative Agricole Unie pour le Palmier</p>
            </td>
        </tr>
    </table>

    <div class="doc-title">BORDEREAU DE DÉCHARGEMENT</div>

    <div class="meta">
        <div><strong>CHARGE DE MISSION :</strong> {{ $agentName }}</div>
        <div><strong>Période du :</strong> {{ $dateDebut }} au {{ $dateFin }}</div>
    </div>

    @forelse ($groups as $group)
        <div class="usine-title">{{ $group['usine'] }}</div>
        <table class="tickets">
            <thead>
                <tr>
                    <th style="width: 14%;">Date Réception</th>
                    <th style="width: 14%;">Date Ticket</th>
                    <th style="width: 14%;">Véhicule</th>
                    <th style="width: 44%;">N° Ticket</th>
                    <th style="width: 14%;">Poids (kg)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['tickets'] as $ticket)
                    <tr>
                        <td>{{ $ticket['date_reception'] }}</td>
                        <td>{{ $ticket['date_ticket'] }}</td>
                        <td>{{ $ticket['vehicule'] }}</td>
                        <td>{{ $ticket['numero_ticket'] }}</td>
                        <td class="num">{{ $ticket['poids'] }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td colspan="4" style="text-align: right;">
                        Sous-total {{ $group['usine'] }} ({{ $group['count'] }} ticket{{ $group['count'] > 1 ? 's' : '' }})
                    </td>
                    <td class="num">{{ $group['poids_formatted'] }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <div class="empty">Aucun ticket trouvé pour cet agent sur la période sélectionnée.</div>
    @endforelse

    @if ($totalTickets > 0)
        <table class="tickets" style="margin-top: 10px;">
            <tbody>
                <tr class="total">
                    <td colspan="4" style="text-align: right;">
                        TOTAL GÉNÉRAL ({{ $totalTickets }} ticket{{ $totalTickets > 1 ? 's' : '' }})
                    </td>
                    <td class="num" style="width: 14%;">{{ $totalPoids }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="signature">
        Fait à Divo, le {{ $generatedAt }}<br>
        <strong>UNIPALM COOP-CA</strong>
    </div>
</body>
</html>
