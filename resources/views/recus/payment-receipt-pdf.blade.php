<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu {{ $recu->numero_recu }} - Unipalm</title>
    <style>
        @page { margin: 10mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
            margin: 0;
        }
        .receipt {
            padding: 8px 0 12px;
            min-height: 125mm;
            position: relative;
        }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .logo { width: 55px; }
        .title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin: 0;
        }
        .recu-num {
            text-align: right;
            font-size: 9px;
            margin-top: 2px;
        }
        .doc-ref {
            text-align: center;
            margin: 10px 0 4px;
            font-size: 10px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin: 12px 0 6px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        .row-line { margin: 4px 0; }
        .label { display: inline-block; width: 110px; }
        .value { font-weight: bold; }
        .amounts {
            margin-top: 10px;
            border-top: 1px solid #999;
            padding-top: 8px;
        }
        .signatures {
            margin-top: 22px;
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding-top: 8px;
        }
        .sig-title { font-weight: bold; font-size: 10px; }
        .sig-name { font-size: 9px; margin-top: 6px; }
        .footer-note {
            text-align: center;
            font-style: italic;
            font-size: 8px;
            margin-top: 10px;
        }
        .cut-line {
            text-align: center;
            margin: 6px 0 10px;
            color: #888;
            font-size: 8px;
            border-top: 1px dashed #aaa;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    @include('recus.partials.receipt-copy', ['copyLabel' => null])

    <div class="cut-line">— DÉCOUPER ICI —</div>

    @include('recus.partials.receipt-copy', ['copyLabel' => 'Exemplaire caisse'])
</body>
</html>
