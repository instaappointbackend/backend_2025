<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the JSON file containing the Firebase Service Account credentials.
    | If not specified, the SDK will attempt to auto-discover it.
    |
    */
    'credentials' => env('FIREBASE_CREDENTIALS', null),

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL
    |--------------------------------------------------------------------------
    |
    | The URL of your Firebase Realtime Database.
    |
    */
    'database_url' => env('FIREBASE_DATABASE_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Firebase FCM Server Key
    |--------------------------------------------------------------------------
    |
    | Your Firebase Cloud Messaging server key.
    |
    */
    'server_key' => env('FCM_SERVER_KEY', 'QoeKTw5ry9B2p8WONe'),
];
