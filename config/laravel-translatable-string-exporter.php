<?php

declare(strict_types=1);

return [
    // Directories to search in.
    'directories' => [
        'app',
        'resources',
        'packages',
    ],

    // Directories to exclude from search.
    //
    // Please note, these directories should be relative to the ones listed in 'directories'.
    // For example, if you have 'resources' in 'directories', then to ignore the 'resources/ignored' directory,
    // you need to add 'ignored' to the 'excluded-directories' list.
    'excluded-directories' => [
        'vendor',
        'node_modules',
        'tests',
    ],

    // File Patterns to search for.
    'patterns' => [
        '*.php',
        '*.js',
    ],

    // Indicates whether new lines are allowed in translations.
    'allow-newlines' => false,

    // Translation function names or a custom transform function.
    // Example of a custom transform function:
    // 'transform' => fn ($s) => \strtoupper(\str_replace(["-","_"], " ", $s))
    // If your function name contains $ escape it using \$ .
    'functions' => [
        '__',
        'trans_choice',
        '_t',
        '@lang',
    ],

    // Indicates whether you need to sort the translations alphabetically
    // by original strings (keys).
    // It helps navigate a translation file and detect possible duplicates.
    'sort-keys' => true,

    // Indicates whether keys from the persistent-strings file should be also added
    // to translation files automatically on export if they don't yet exist there.
    'add-persistent-strings-to-translations' => false,

    // Indicates whether it's necessary to exclude Laravel translation keys
    // from the resulting language file if they have corresponding translations
    // in the given language.
    // This option allows correctly combine two translation approaches:
    // Laravel translation keys (PHP) and translatable strings (JSON).
    'exclude-translation-keys' => true,

    // Indicates whether you need to put untranslated strings
    // at the top of a translation file.
    // The criterion of whether a string is untranslated is
    // if its key and value are equivalent.
    // If sorting is enabled, untranslated and translated strings are sorted separately.
    'put-untranslated-strings-at-the-top' => false,
];
