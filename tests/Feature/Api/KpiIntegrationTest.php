<?php

test('KPI endpoints authenticate against the configured API key', function () {
    config(['services.kpi.api_key' => 'test-kpi-key']);

    $this->getJson('/api/v1/kpi-active-employees')
        ->assertUnauthorized();

    $this->withHeader('X-API-KEY', 'test-kpi-key')
        ->getJson('/api/v1/kpi-active-employees')
        ->assertOk()
        ->assertJsonPath('status', 'success');
});
