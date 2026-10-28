<?php

$proxies = trim((string) env('TRUSTED_PROXIES', ''));

return [
    'proxies' => $proxies === ''
        ? null
        : array_values(array_filter(array_map('trim', explode(',', $proxies)))),
];
