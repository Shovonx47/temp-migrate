<?php

return [
    'source' => ['app', 'database', 'routes'],
    'destination' => env('ENCRYPTER_DESTINATION', 'dist'),
    'key_length' => 6,

    // File list is sourced from `git ls-files` (so .gitignore is respected automatically).
    // Add anything here that IS tracked in git but should NOT ship to the encrypted copy.
    // The destination directory itself is always excluded.
    'exclude' => [
        // Hide the encrypter internals from the deployed copy
        'config/encrypter.php',
        'app/Console/Commands/EncryptApplication.php',

        // Project meta files not needed on the server
        'CLAUDE.md',
        'README.md',
        '.githooks',
        '.gitattributes',
        '.agents',
        '.aignore',
        '.codex',
        '.editorconfig',
        '.mcplugins',
        '.claude',
        '.ai',
    ],
];
