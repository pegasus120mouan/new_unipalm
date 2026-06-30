<?php

return [

  'endpoint' => env('MINIO_ENDPOINT', env('AWS_ENDPOINT', 'http://51.178.49.141:9000')),

  'access_key' => env('MINIO_ACCESS_KEY', env('AWS_ACCESS_KEY_ID')),

  'secret_key' => env('MINIO_SECRET_KEY', env('AWS_SECRET_ACCESS_KEY')),

  'region' => env('MINIO_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),

  'bucket' => env('MINIO_BUCKET', env('AWS_BUCKET', 'unipalm')),

  'planteurs_bucket' => env('MINIO_PLANTEURS_BUCKET', 'planteurs'),

  'users_prefix' => env('MINIO_USERS_PREFIX', 'utilisateurs'),

  'agents_documents_prefix' => env('MINIO_AGENTS_DOCUMENTS_PREFIX', 'agents/documents'),

];
