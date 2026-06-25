<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau {{ $bordereau->numero_bordereau }} - Unipalm</title>
    <style>
        @page { margin: 20mm 15mm 25mm 15mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
            margin: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 18px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
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
            margin: 12px 0 16px;
        }
        .info-title {
            background: #34495e;
            color: #fff;
            text-align: center;
            font-weight: bold;
            padding: 8px;
            border: 1px solid #34495e;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .info-grid th,
        .info-grid td {
            border: 1px solid #ccc;
            padding: 7px 8px;
        }
        .info-grid th {
            background: #f8f9fa;
            font-weight: bold;
            text-align: left;
        }
        .info-grid .poids { color: #16a085; }
        .info-grid .montant { color: #e74c3c; }
        .info-grid .creation {
            text-align: center;
            background: #f8f9fa;
            font-weight: bold;
        }
        .tickets {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .tickets th,
        .tickets td {
            border: 1px solid #999;
            padding: 5px 4px;
        }
        .tickets th {
            background: #c5d9f1;
            font-weight: bold;
            text-align: center;
        }
        .tickets td.num,
        .tickets td.right { text-align: right; }
        .tickets td.center { text-align: center; }
        .subtotal td {
            background: #f0f0f0;
            font-style: italic;
            font-weight: bold;
        }
        .total td {
            background: #c5d9f1;
            font-weight: bold;
        }
        .signatures {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px;
            border: none;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            height: 40px;
            margin-top: 8px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #333;
            border-top: 1px solid #90ee90;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
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
    </div>

    <div class="doc-title">
        BORDEREAU DE DÉCHARGEMENT N° {{ $bordereau->numero_bordereau }}
    </div>

    <div class="info-title">INFORMATIONS DU BORDEREAU</div>
    <table class="info-grid">
        <tr>
            <th style="width: 50%;">AGENT RESPONSABLE</th>
            <th style="width: 50%;">PÉRIODE DE COLLECTE</th>
        </tr>
        <tr>
            <td>{{ $agentName }}</td>
            <td>
                {{ $bordereau->date_debut?->format('d/m/Y') ?? '-' }}
                au
                {{ $bordereau->date_fin?->format('d/m/Y') ?? '-' }}
            </td>
        </tr>
        <tr>
            <th>POIDS TOTAL COLLECTÉ</th>
            <th>MONTANT TOTAL</th>
        </tr>
        <tr>
            <td class="poids">{{ number_format($totalPoids, 0, ',', ' ') }} KG</td>
            <td class="montant">{{ number_format($totalMontant, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td colspan="2" class="creation">DATE DE CRÉATION</td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center;">
                {{ $bordereau->created_at?->format('d/m/Y à H:i') ?? '-' }}
            </td>
        </tr>
    </table>

    <table class="tickets">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 28%;">N° Ticket</th>
                <th style="width: 14%;">Usine</th>
                <th style="width: 14%;">Véhicule</th>
                <th style="width: 12%;">Poids (Kg)</th>
                <th style="width: 10%;">Prix Unit.</th>
                <th style="width: 14%;">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $group)
                @foreach ($group['tickets'] as $ticket)
                    <tr>
                        <td class="center">{{ $ticket['date'] }}</td>
                        <td class="center">{{ $ticket['numero'] }}</td>
                        <td>{{ $ticket['usine'] }}</td>
                        <td class="center">{{ $ticket['vehicule'] }}</td>
                        <td class="num">{{ number_format($ticket['poids'], 0, ',', ' ') }}</td>
                        <td class="num">{{ number_format($ticket['prix_unitaire'], 0, ',', ' ') }}</td>
                        <td class="num">{{ number_format($ticket['montant'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td colspan="4" class="right">Sous-total {{ $group['usine'] }}</td>
                    <td class="num">{{ number_format($group['poids'], 0, ',', ' ') }}</td>
                    <td></td>
                    <td class="num">{{ number_format($group['montant'], 0, ',', ' ') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="4" class="right">TOTAL GENERAL</td>
                <td class="num">{{ number_format($totalPoids, 0, ',', ' ') }}</td>
                <td></td>
                <td class="num">{{ number_format($totalMontant, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>Signature de l'agent:</td>
            <td>Signature du responsable:</td>
        </tr>
        <tr>
            <td><div class="signature-line"></div></td>
            <td><div class="signature-line"></div></td>
        </tr>
    </table>

    <div class="footer">
        Siège Social : Divo Quartier millionnaire non loin de l'hôtel Boya<br>
        NCC : 2050R910 / TEL : (00225) 27 34 75 92 36 / 07 49 17 16 32
    </div>
</body>
</html>
