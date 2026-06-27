<?php

return [

    'api_key' => env('VERIF_API_KEY'),

    'usines_per_page' => (int) env('VERIF_USINES_PER_PAGE', 20),

    'tickets_per_page' => (int) env('VERIF_TICKETS_PER_PAGE', 50),

];
