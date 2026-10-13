<?php

return [
    /*
    | URL prefix of the admin panel (ADR-01). Change it to obscure the panel URL; route names stay `admin.*`.
    */
    'path' => trim((string) env('ADMIN_PATH', 'admin'), '/') ?: 'admin',
];
