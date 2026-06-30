<div class="receipt">
    <table class="header-table">
        <tr>
            <td style="width: 70px;">
                @if ($logoPath)
                    <img src="{{ $logoPath }}" class="logo" alt="Logo">
                @endif
            </td>
            <td>
                <p class="title">Reçu de Paiement</p>
                @if ($copyLabel)
                    <p class="doc-ref" style="font-size: 8px; color: #666;">{{ $copyLabel }}</p>
                @endif
            </td>
            <td style="width: 90px;">
                <div class="recu-num">N° {{ $recu->numero_recu }}</div>
            </td>
        </tr>
    </table>

    <p class="doc-ref">N° {{ $typeDocument }} : {{ $recu->numero_document }}</p>
    <p class="doc-ref">Date : {{ $dateCreation->format('d/m/Y H:i') }}</p>

    <div class="section-title">Informations Agent</div>
    <div class="row-line"><span class="label">Nom de l'agent :</span> <span class="value">{{ $recu->nom_agent }}</span></div>
    <div class="row-line"><span class="label">Contact :</span> <span class="value">{{ $recu->contact_agent ?? '—' }}</span></div>

    @if ($recu->nom_usine)
        <div class="section-title">Informations Transport</div>
        <div class="row-line"><span class="label">Usine :</span> <span class="value">{{ $recu->nom_usine }}</span></div>
        @if ($recu->matricule_vehicule)
            <div class="row-line"><span class="label">Véhicule :</span> <span class="value">{{ $recu->matricule_vehicule }}</span></div>
        @endif
    @endif

    <div class="amounts">
        <div class="row-line"><span class="label">Montant total :</span> <span class="value">{{ number_format((float) $recu->montant_total, 0, ',', ' ') }} FCFA</span></div>
        <div class="row-line"><span class="label">Montant payé :</span> <span class="value">{{ number_format((float) $recu->montant_paye, 0, ',', ' ') }} FCFA</span></div>
        @if ((float) $recu->montant_precedent > 0)
            <div class="row-line"><span class="label">Déjà payé avant :</span> <span class="value">{{ number_format((float) $recu->montant_precedent, 0, ',', ' ') }} FCFA</span></div>
        @endif
        <div class="row-line">
            <span class="label">Source de paiement :</span>
            <span class="value">
                {{ $sourceLabel }}
                @if ($recu->source_paiement === 'cheque' && $recu->numero_cheque)
                    <em>N° {{ $recu->numero_cheque }}</em>
                @endif
            </span>
        </div>
        <div class="row-line"><span class="label">Reste à payer :</span> <span class="value">{{ number_format((float) $recu->reste_a_payer, 0, ',', ' ') }} FCFA</span></div>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-title">Signature Caissier</div>
                <div class="sig-name">{{ $recu->nom_caissier }}</div>
            </td>
            <td>
                <div class="sig-title">Signature Récepteur</div>
                <div class="sig-name">{{ $recu->nom_agent }}</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">Fait à Abidjan, le {{ $dateCreation->format('d/m/Y') }}</div>
</div>
