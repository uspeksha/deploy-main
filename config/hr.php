<?php

return [
    'loan' => [
        'max_principal' => (int) env('HR_LOAN_MAX_PRINCIPAL', 5_000_000),
        'max_tenor_months' => (int) env('HR_LOAN_MAX_TENOR_MONTHS', 12),
    ],
];
