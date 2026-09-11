<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HIS sync agent
    |--------------------------------------------------------------------------
    |
    | The agent runs on the clinic server, reads the Bina HIS database read-only
    | and pushes batches to /api/his/sync/*. There is deliberately no default
    | key: an unset secret must fail closed, because these endpoints carry
    | patient identifiers.
    |
    */

    'enabled' => env('HIS_SYNC_ENABLED', false),

    'key' => env('HIS_AGENT_KEY'),

    // Optional. When set, every request must carry a matching HMAC signature.
    'secret' => env('HIS_AGENT_SECRET'),

    // Requests older than this (by their X-His-Timestamp header) are replays.
    'max_skew_seconds' => (int) env('HIS_AGENT_MAX_SKEW', 300),

    // Optional comma separated allow list of clinic IPs.
    'allowed_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('HIS_AGENT_ALLOWED_IPS', ''))
    ))),

    'max_rows_per_batch' => (int) env('HIS_AGENT_MAX_ROWS', 500),

    /*
    |--------------------------------------------------------------------------
    | Status mapping
    |--------------------------------------------------------------------------
    |
    | HIS status codes are unknown until the discovery script has run against a
    | real database, so the mapping lives in config rather than in code. Keys
    | are compared case-insensitively against the raw HIS value; anything that
    | does not match falls back to `default`.
    |
    */

    'status_map' => [
        'default' => 'scheduled',

        'appointments' => [
            'reserved' => 'scheduled',
            'confirmed' => 'confirmed',
            'admitted' => 'waiting',
            'inprogress' => 'in_consult',
            'visited' => 'done',
            'done' => 'done',
            'completed' => 'done',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'noshow' => 'no_show',
            'absent' => 'no_show',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Patient identity
    |--------------------------------------------------------------------------
    |
    | `patients.national_code` is unique but nullable, and MySQL allows many
    | NULLs in a unique index. Matching on an empty code would merge unrelated
    | people, so HIS patients without one get a synthetic code instead. This
    | mirrors the existing `wp` prefix used by the WordPress importer.
    |
    */

    'synthetic_national_code_prefix' => 'his',

];
