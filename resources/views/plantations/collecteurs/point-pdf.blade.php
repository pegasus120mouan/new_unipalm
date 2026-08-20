<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Point plantations — Collecteur #{{ $collecteurId }} - Unipalm</title>
    <style>
        @page { margin: 12mm 10mm 14mm 10mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #222;
            margin: 0;
        }
        .header { text-align: center; margin-bottom: 12px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .logo { width: 55px; }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #008000;
            margin: 0;
        }
        .company-subtitle {
            font-size: 10px;
            color: #666;
            margin: 3px 0 0;
        }
        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 10px 0 12px;
            text-transform: uppercase;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .meta-label {
            font-weight: bold;
            width: 110px;
        }
        .section-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin: 10px 0 6px;
            text-transform: uppercase;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
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
            font-size: 8px;
            text-transform: uppercase;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .empty {
            text-align: center;
            font-style: italic;
            padding: 14px;
            color: #666;
        }
        .footer-note {
            margin-top: 14px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        .summary-value { font-weight: bold; }
    </style>
</head>
<body>
    @php
        $fullName = trim(($collecteur['nom'] ?? '').' '.($collecteur['prenoms'] ?? ''));
        $zoneName = $collecteur['zone_nom'] ?? $collecteur['nom_zone'] ?? 'Non assigné';
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                @if ($logoPath)
                    <td style="width: 70px;">
                        <img src="{{ $logoPath }}" alt="Logo" class="logo">
                    </td>
                @endif
                <td>
                    <p class="company-name">UNIPALM COOP</p>
                    <p class="company-subtitle">Point des plantations par collecteur</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-title">Point des plantations</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Collecteur :</td>
            <td>{{ $fullName !== '' ? $fullName : 'Collecteur #'.$collecteurId }}</td>
            <td class="meta-label">Contact :</td>
            <td>{{ $collecteur['contact'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Zone :</td>
            <td>{{ $zoneName }}</td>
            <td class="meta-label">Période :</td>
            <td>
                Du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }}
                au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td class="meta-label">Date d'édition :</td>
            <td colspan="3">{{ $generatedAt->format('d/m/Y à H:i') }}</td>
        </tr>
    </table>

    <div class="section-title">Résumé de la période</div>
    <table class="data">
        <thead>
            <tr>
                <th>Planteurs recensés</th>
                <th>Superficie totale (ha)</th>
                <th>Parcelles enregistrées</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center summary-value">{{ number_format((float) ($stats['nombre_exploitants'] ?? 0), 0, ',', ' ') }}</td>
                <td class="text-center summary-value">{{ number_format((float) ($stats['superficie_totale'] ?? 0), 2, ',', ' ') }}</td>
                <td class="text-center summary-value">{{ number_format((float) ($stats['nombre_parcelles'] ?? 0), 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Liste des plantations</div>

    @if (empty($planteurs))
        <p class="empty">Aucune plantation trouvée pour cette période.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 4%;">N°</th>
                    <th style="width: 10%;">N° fiche</th>
                    <th style="width: 18%;">Nom &amp; Prénoms</th>
                    <th style="width: 10%;">Téléphone</th>
                    <th style="width: 12%;">Région</th>
                    <th style="width: 12%;">Sous-préfecture</th>
                    <th style="width: 12%;">Village</th>
                    <th style="width: 10%;">Superficie (ha)</th>
                    <th style="width: 10%;">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($planteurs as $index => $planteur)
                    @php
                        $exploitation = is_array($planteur['exploitation'] ?? null) ? $planteur['exploitation'] : [];
                        $cultures = is_array($planteur['cultures'] ?? null) ? $planteur['cultures'] : [];
                        $superficie = 0.0;
                        foreach ($cultures as $culture) {
                            if (is_array($culture)) {
                                $superficie += (float) ($culture['superficie_ha'] ?? 0);
                            }
                        }
                        $dateRaw = $planteur['date_enregistrement'] ?? $planteur['created_at'] ?? null;
                        $dateLabel = $dateRaw
                            ? \Carbon\Carbon::parse($dateRaw)->format('d/m/Y')
                            : '—';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">{{ $planteur['numero_fiche'] ?? '—' }}</td>
                        <td>{{ $planteur['nom_prenoms'] ?? '—' }}</td>
                        <td class="text-center">{{ $planteur['telephone'] ?? 'N/A' }}</td>
                        <td>{{ $exploitation['region'] ?? 'N/A' }}</td>
                        <td>{{ $exploitation['sous_prefecture_village'] ?? 'N/A' }}</td>
                        <td>{{ $exploitation['village'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($superficie, 2, ',', ' ') }}</td>
                        <td class="text-center">{{ $dateLabel }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footer-note">
        Document généré le {{ $generatedAt->format('d/m/Y à H:i') }} par le système UniPalm
        — {{ count($planteurs) }} plantation(s) listée(s)
    </p>
</body>
</html>
