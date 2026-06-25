@php
    $hasData = (float) $total > 0;
    $pctPaye = $hasData ? max(0, min(100, (int) round(((float) $paye / (float) $total) * 100))) : 0;
    $pctReste = $hasData ? max(0, min(100, (int) round(((float) $reste / (float) $total) * 100))) : 0;
@endphp

@if ($hasData)
    <div class="compte-agent-stats" style="min-width: 220px;">
        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Total montant</span>
            <span>100%</span>
        </div>
        <div class="progress mb-2" style="height: 6px;">
            <div class="progress-bar bg-warning" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
        </div>

        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Montant payé</span>
            <span>{{ $pctPaye }}%</span>
        </div>
        <div class="progress mb-2" style="height: 6px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctPaye }}%;" aria-valuenow="{{ $pctPaye }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>

        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Reste à payer</span>
            <span>{{ $pctReste }}%</span>
        </div>
        <div class="progress mb-0" style="height: 6px;">
            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $pctReste }}%;" aria-valuenow="{{ $pctReste }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
@else
    <span class="text-muted small">Aucune donnée</span>
@endif
