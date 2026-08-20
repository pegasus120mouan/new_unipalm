<?php

return [

    'api_base' => rtrim(env(
        'PLANTEUR_API_BASE',
        'https://api.objetombrepegasus.online/api/planteur/actions'
    ), '/'),

    'stats_url' => env(
        'PLANTEUR_STATS_URL',
        'https://api.objetombrepegasus.online/api/planteur/actions/api_stats_global.php'
    ),

    'minio_bucket' => env('MINIO_PLANTEURS_BUCKET', 'planteurs'),

    'utilisateurs_url' => env(
        'PLANTEUR_UTILISATEURS_URL',
        'https://api.objetombrepegasus.online/api/planteur/actions/utilisateurs.php'
    ),

    'regions_url' => env(
        'PLANTEUR_REGIONS_URL',
        'https://api.objetombrepegasus.online/api/planteur/actions/api_regions.php'
    ),

    'zones_url' => env(
        'PLANTEUR_ZONES_URL',
        'https://api.objetombrepegasus.online/api/planteur/actions/api_zones.php'
    ),

    'doublons_url' => env(
        'PLANTEUR_DOUBLONS_URL',
        'https://api.objetombrepegasus.online/api/planteur/actions/doublons_planteurs.php'
    ),

    'stats_collecteur_url' => env(
        'PLANTEUR_STATS_COLLECTEUR_URL',
        'https://api.objetombrepegasus.online/api/planteur/actions/api_stats_collecteur.php'
    ),

];
