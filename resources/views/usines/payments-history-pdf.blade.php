<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des paiements — {{ $usine->nom_usine }} - Unipalm</title>
    <style>
        @page { margin: 18mm 14mm 22mm 14mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
            margin: 0;
        }
        .header { text-align: center; margin-bottom: 16px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .logo { width: 65px; }
        .company-name {
            font-size: 17px;
            font-weight: bold;
            color: #008000;
            margin: 0;
        }
        .company-subtitle {
            font-size: 10px;
            color: #666;
            margin: 4px 0 0;
        }
        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 14px 0 16px;
            text-transform: uppercase;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .meta-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .meta-label {
            font-weight: bold;
            width: 120px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.data th,
        table.data td {
            border: 1px solid #444;
            padding: 6px 5px;
        }
        table.data th {
            background: #667eea;
            color: #fff;
            font-weight: bold;
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .empty {
            text-align: center;
            font-style: italic;
            padding: 16px;
            color: #666;
        }
        .footer-note {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .total-row td {
            font-weight: bold;
            background: #f8f9fa;
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
                    <p class="company-name">UNIPALM COOP</p>
                    <p class="company-subtitle">Historique des paiements usine</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-title">Historique des paiements</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Usine :</td>
            <td>{{ $usine->nom_usine }}</td>
        </tr>
        <tr>
            <td class="meta-label">Date d'édition :</td>
            <td>{{ $generatedAt->format('d/m/Y à H:i') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Nombre de paiements :</td>
            <td>{{ $payments->count() }}</td>
        </tr>
    </table>

    @if ($payments->isEmpty())
        <p class="empty">Aucun paiement enregistré pour cette usine.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 8%;">N°</th>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 20%;">Montant (FCFA)</th>
                    <th style="width: 20%;">Mode</th>
                    <th>Référence</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td class="text-center">{{ $payment->id }}</td>
                        <td class="text-center">{{ $payment->date_paiement?->format('d/m/Y') ?? '-' }}</td>
                        <td class="text-right">{{ number_format((float) $payment->montant, 0, '', ' ') }}</td>
                        <td class="text-center">{{ $payment->mode_paiement }}</td>
                        <td>{{ $payment->reference_paiement ?: '—' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="text-right">Total :</td>
                    <td class="text-right">{{ number_format((float) $totalMontant, 0, '', ' ') }} FCFA</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    @endif

    <p class="footer-note">
        Document généré le {{ $generatedAt->format('d/m/Y à H:i') }} par le système UniPalm
    </p>
</body>
</html>
