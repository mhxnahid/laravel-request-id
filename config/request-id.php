<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | When false the middleware passes the request through untouched: no IDs
    | are resolved, no headers are added and nothing is pushed to the log.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto-register middleware
    |--------------------------------------------------------------------------
    |
    | When true the middleware is prepended to the global HTTP stack so every
    | request gets an ID without touching the kernel. Set to false to register
    | it manually using the "request-id" alias.
    |
    */
    'register_global_middleware' => true,

    /*
    |--------------------------------------------------------------------------
    | Header names
    |--------------------------------------------------------------------------
    |
    | The incoming/outgoing header for each ID.
    |
    */
    'headers' => [
        'request_id'     => 'X-Request-Id',
        'session_id'     => 'X-Session-Id',
        'correlation_id' => 'X-Correlation-Id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Generate when absent
    |--------------------------------------------------------------------------
    |
    | IDs marked true are generated as a UUID v4 when missing or invalid on the
    | incoming request. IDs marked false are only propagated from upstream and
    | left null when the header is absent.
    |
    */
    'generate' => [
        'request_id'     => true,
        'session_id'     => false,
        'correlation_id' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Request attribute keys
    |--------------------------------------------------------------------------
    |
    | The keys the resolved IDs are stored under on $request->attributes, where
    | downstream code can read them with $request->attributes->get(...).
    |
    */
    'attributes' => [
        'request_id'     => 'x_request_id',
        'session_id'     => 'x_session_id',
        'correlation_id' => 'x_correlation_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Master switch for attaching the IDs (and user) to log records. When false
    | the IDs are still resolved and echoed on the response, but nothing is
    | pushed to the log.
    |
    */
    'log' => true,

    /*
    |--------------------------------------------------------------------------
    | Log channels
    |--------------------------------------------------------------------------
    |
    | When logging is enabled the IDs are always pushed to the default log
    | driver. List any additional channels here to attach the same processor to
    | them. Unknown channels are skipped silently so a missing channel never
    | breaks the request.
    |
    */
    'log_channels' => [],

    /*
    |--------------------------------------------------------------------------
    | Log authenticated user
    |--------------------------------------------------------------------------
    |
    | When true the fields below are added to every log record for the
    | authenticated user. Each entry maps a log record key to either a model
    | attribute name or a callable that receives the user and returns a value.
    |
    */
    'log_user' => true,

    'user_fields' => [
        'user_id'    => 'id',
        'user_email' => 'email',
        'user_phone' => 'phone',
    ],
];
