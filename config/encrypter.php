<?php

return [
    'source' => ['app', 'database', 'routes'],
    'destination' => env('ENCRYPTER_DESTINATION', 'dist'),
    'key_length' => 6,
];
