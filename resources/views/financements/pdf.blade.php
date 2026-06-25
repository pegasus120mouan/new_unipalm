<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique financements — {{ $agent->full_name }} - Unipalm</title>
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
        .section-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin: 14px 0 8px;
            text-transform: uppercase;
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
        .positive { color: #198754; }
        .negative { color: #dc3545; }
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
                    <p class="company-subtitle">Historique des financements</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-title">Historique des financements</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Agent :</td>
            <td>{{ $agent->full_name }}</td>
        </tr>
        @if ($agent->groupe)
            <tr>
                <td class="meta-label">Chef d'équipe :</td>
                <td>{{ $agent->groupe->full_name }}</td>
            </tr>
        @endif
        <tr>
            <td class="meta-label">Période :</td>
            <td>Du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Date d'édition :</td>
            <td>{{ $generatedAt->format('d/m/Y à H:i') }}</td>
        </tr>
    </table>

    <div class="section-title">Résumé financier</div>
    <table class="data">
        <thead>
            <tr>
                <th>Type</th>
                <th>Montant (FCFA)</th>
                <th>Nombre d'opérations</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Financements accordés</td>
                <td class="text-right">{{ number_format($stats['total_financements'], 0, '', ' ') }}</td>
                <td class="text-center">{{ $stats['nb_financements'] }}</td>
            </tr>
            <tr>
                <td>Remboursements</td>
                <td class="text-right">{{ number_format($stats['total_remboursements'], 0, '', ' ') }}</td>
                <td class="text-center">{{ $stats['nb_remboursements'] }}</td>
            </tr>
            <tr>
                <td><strong>Solde de la période</strong></td>
                <td class="text-right"><strong>{{ number_format($stats['solde_periode'], 0, '', ' ') }}</strong></td>
                <td class="text-center"><strong>{{ $financements->count() }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Détail des opérations</div>

    @if ($financements->isEmpty())
        <p class="empty">Aucun financement trouvé pour cette période.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 12%;">Numéro</th>
                    <th style="width: 18%;">Type</th>
                    <th style="width: 18%;">Montant (FCFA)</th>
                    <th>Motif</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($financements as $financement)
                    <tr>
                        <td class="text-center">{{ $financement->date_financement?->format('d/m/Y') ?? '-' }}</td>
                        <td class="text-center">{{ $financement->Numero_financement }}</td>
                        <td class="text-center">
                            {{ $financement->isAdvance() ? 'Financement' : 'Remboursement' }}
                        </td>
                        <td class="text-right {{ $financement->isAdvance() ? 'positive' : 'negative' }}">
                            {{ $financement->isAdvance() ? '+' : '' }}{{ number_format((float) $financement->montant, 0, '', ' ') }}
                        </td>
                        <td>{{ $financement->motif ?: 'Aucun motif' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footer-note">
        Document généré le {{ $generatedAt->format('d/m/Y à H:i') }} par le système UniPalm
    </p>
</body>
</html>
