<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Cart-Token', 'X-Pagination-Current-Page', 'X-Pagination-Per-Page', 'X-Pagination-Total', 'X-Pagination-Total-Pages'],

    'max_age' => 0,

    'supports_credentials' => false,

];