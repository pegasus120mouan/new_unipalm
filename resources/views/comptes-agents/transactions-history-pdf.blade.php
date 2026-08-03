<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des transactions — {{ $agent->full_name }} - Unipalm</title>
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
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta-table td { padding: 4px 0; vertical-align: top; }
        .meta-label { font-weight: bold; width: 120px; }
        .section-title {
            background: #e6f0ff;
            font-weight: bold;
            font-size: 11px;
            padding: 6px 8px;
            margin: 16px 0 8px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.data th,
        table.data td {
            border: 1px solid #444;
            padding: 5px 4px;
        }
        table.data th {
            background: #667eea;
            color: #fff;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .empty {
            font-style: italic;
            color: #666;
            padding: 8px 0;
        }
        .total-row td {
            font-weight: bold;
            background: #f5f5f5;
        }
        .footer-note {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                @if ($logoPath)
                    <td style="width: 70px;">
                        <img src="{{ $logoPath }}" class="logo" alt="Logo">
                    </td>
                @endif
                <td>
                    <p class="company-name">UNIPALM COOP</p>
                    <p class="company-subtitle">Historique des transactions</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Agent :</td>
            <td>{{ $agent->full_name }}</td>
        </tr>
        <tr>
            <td class="meta-label">Période :</td>
            <td>
                du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }}
                au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td class="meta-label">Généré le :</td>
            <td>{{ $generatedAt->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="section-title">1. Paiements enregistrés</div>

    @if ($paiements->isNotEmpty())
        <table class="data">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>N° Reçu</th>
                    <th>Document</th>
                    <th>Payé</th>
                    <th>Total</th>
                    <th>Reste</th>
                    <th>Src</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($paiements as $paiement)
                    <tr>
                        <td>{{ $paiement->date_creation ? \Carbon\Carbon::parse($paiement->date_creation)->format('d/m/Y H:i') : '—' }}</td>
                        <td>{{ $paiement->numero_recu }}</td>
                        <td>{{ $paiement->numero_document ?? '—' }}</td>
                        <td class="text-right">{{ number_format((float) $paiement->montant_paye, 0, '', ' ') }}</td>
                        <td class="text-right">{{ number_format((float) $paiement->montant_total, 0, '', ' ') }}</td>
                        <td class="text-right">{{ number_format((float) $paiement->reste_a_payer, 0, '', ' ') }}</td>
                        <td class="text-center">
                            @php
                                $src = match ($paiement->source_paiement ?? '') {
                                    'financement' => 'FIN',
                                    'cheque' => 'CHEQUE',
                                    default => 'CAIS',
                                };
                            @endphp
                            {{ $src }}
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="text-right">Total montants payés</td>
                    <td class="text-right">{{ number_format($totalPaye, 0, '', ' ') }} FCFA</td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="empty">Aucun paiement enregistré pour cette période.</p>
    @endif

    <div class="section-title">2. Financements</div>

    @if ($financements->isNotEmpty())
        <table class="data">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>N° Financement</th>
                    <th>Montant</th>
                    <th>Motif</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($financements as $financement)
                    <tr>
                        <td>{{ $financement->date_financement?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $financement->code_affiche }}</td>
                        <td class="text-right">{{ number_format((float) $financement->montant, 0, '', ' ') }}</td>
                        <td>{{ $financement->motif_affiche }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="text-right">Total financements (+)</td>
                    <td class="text-right">{{ number_format($financementStats['total_financements'], 0, '', ' ') }} FCFA</td>
                    <td></td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" class="text-right">Total remboursements (-)</td>
                    <td class="text-right">{{ number_format($financementStats['total_remboursements'], 0, '', ' ') }} FCFA</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="empty">Aucun mouvement de financement pour cette période.</p>
    @endif

    <p class="footer-note">Document généré automatiquement par Unipalm</p>
</body>
</html>
