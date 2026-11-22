<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Deployment mode
    |--------------------------------------------------------------------------
    |
    | "single": one install serves exactly one school (the packaged product).
    |           The school is resolved automatically and public registration
    |           is closed. Create it with: php artisan kcam:install
    | "multi":  one install hosts many schools (hosted SaaS). The school comes
    |           from the logged in user and public registration is open.
    |
    */

    'mode' => env('KCAM_MODE', 'single'),

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    |
    | Switch modules on or off per customer to sell different packages. A
    | disabled module disappears from the menu and its routes return 404.
    |
    */

    'modules' => [
        'students' => (bool) env('KCAM_MODULE_STUDENTS', true),
        'attendance' => (bool) env('KCAM_MODULE_ATTENDANCE', true),
        'fees' => (bool) env('KCAM_MODULE_FEES', true),
        'exams' => (bool) env('KCAM_MODULE_EXAMS', true),
        'notices' => (bool) env('KCAM_MODULE_NOTICES', true),
    ],

];
