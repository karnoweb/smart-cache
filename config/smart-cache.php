<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | Which Laravel cache store SmartCache should use. If null, it uses
    | the default store from config('cache.default').
    |
    */
    'store' => env('SMART_CACHE_STORE', null),

    /*
    |--------------------------------------------------------------------------
    | Default TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Default time-to-live for cached items when no TTL is specified.
    | Set to null for no expiration.
    |
    */
    'default_ttl' => env('SMART_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Key Prefix
    |--------------------------------------------------------------------------
    |
    | A global prefix prepended to all SmartCache keys, useful for
    | avoiding collisions in shared cache environments.
    |
    */
    'global_prefix' => env('SMART_CACHE_PREFIX', 'sc'),

    /*
    |--------------------------------------------------------------------------
    | Auto-Flush Strategy
    |--------------------------------------------------------------------------
    |
    | Controls how aggressively model event listeners flush cache.
    |
    | Supported: "all", "selective", "none"
    |
    | - "all": flush all keys for the model on any change (current behavior)
    | - "selective": only flush keys registered as "auto_flush"
    | - "none": disable auto-flush entirely
    |
    */
    'flush_strategy' => env('SMART_CACHE_FLUSH_STRATEGY', 'all'),

    /*
    |--------------------------------------------------------------------------
    | Stampede Protection
    |--------------------------------------------------------------------------
    |
    | Enable cache lock during remember() to prevent thundering herd.
    |
    */
    'stampede_protection' => env('SMART_CACHE_STAMPEDE_PROTECTION', true),

    /*
    |--------------------------------------------------------------------------
    | Lock Timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | How long to wait to acquire a lock before giving up and running
    | the callback anyway.
    |
    */
    'lock_timeout' => env('SMART_CACHE_LOCK_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Lock Wait (seconds)
    |--------------------------------------------------------------------------
    */
    'lock_wait' => env('SMART_CACHE_LOCK_WAIT', 5),
];
