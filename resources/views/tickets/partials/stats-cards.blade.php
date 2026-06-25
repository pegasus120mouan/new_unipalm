<section class="row mb-4">
    <div class="col-6 col-lg-3 col-md-6">
        <div class="card text-white border-0 h-100" style="background-color: #17a2b8;">
            <div class="card-body text-center py-4">
                <h2 class="fw-bold mb-1">{{ number_format($ticketStats['total'], 0, '', ' ') }}</h2>
                <p class="mb-0">Nombre ticket Total</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 col-md-6">
        <div class="card text-white border-0 h-100" style="background-color: #dc3545;">
            <div class="card-body text-center py-4">
                <h2 class="fw-bold mb-1">{{ number_format($ticketStats['en_attente'], 0, '', ' ') }}</h2>
                <p class="mb-0">Nombre de tickets en attente</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 col-md-6">
        <div class="card border-0 h-100 text-dark" style="background-color: #ffc107;">
            <div class="card-body text-center py-4">
                <h2 class="fw-bold mb-1">{{ number_format($ticketStats['valides'], 0, '', ' ') }}</h2>
                <p class="mb-0">Total tickets validés</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 col-md-6">
        <div class="card text-white border-0 h-100" style="background-color: #8b1538;">
            <div class="card-body text-center py-4">
                <h2 class="fw-bold mb-1">{{ number_format($ticketStats['valides_non_payes'], 0, '', ' ') }}</h2>
                <p class="mb-0">Nombre de ticket VALIDES et non payés</p>
            </div>
        </div>
    </div>
</section>
