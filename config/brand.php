<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Brand Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized branding details to avoid hardcoded visual or textual
    | values in layouts, templates, receipts, and user interfaces.
    |
    | To rebrand the application after a logo change, update only these
    | .env values and replace the logo image file — no code changes needed.
    |
    */

    'name' => env('BRAND_NAME', 'ALJA'),
    'subname' => env('BRAND_SUBNAME', 'POS'),
    'fullname' => env('BRAND_FULLNAME', 'ALJA Digital Printing'),
    'slogan' => env('BRAND_SLOGAN', 'High-Quality Print & Production Shop'),
    'footer' => env('BRAND_FOOTER', 'ALJA Digital Printing. All rights reserved.'),

    /*
    |--------------------------------------------------------------------------
    | Brand Colors
    |--------------------------------------------------------------------------
    |
    | Derived from the company logo. These are injected as CSS custom
    | properties on the <html> element at runtime via layouts/app.blade.php,
    | overriding the @theme defaults in app.css without a rebuild.
    |
    */

    'colors' => [
        'primary' => env('BRAND_COLOR_PRIMARY', '#E91E8C'),
        'secondary' => env('BRAND_COLOR_SECONDARY', '#00AEEF'),
        'accent' => env('BRAND_COLOR_ACCENT', '#FFF200'),
        'dark' => env('BRAND_COLOR_DARK', '#1A1A2E'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand Logo
    |--------------------------------------------------------------------------
    |
    | Path to the logo image file relative to the public/ directory.
    | When null, brand-logo.blade.php falls back to the inline text + dots
    | rendering. To swap the logo, set this env variable and drop the new
    | image file into public/ — no Blade edits required.
    |
    | Example: BRAND_LOGO_PATH=images/logo.png
    |
    */

    'logo' => env('BRAND_LOGO_PATH', 'images/Logo_Alja.png'),
];
