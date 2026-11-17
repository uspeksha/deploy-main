<?php

test('public web registration is disabled and not linked from login', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();

    $this->get('/login')
        ->assertOk()
        ->assertDontSee('Daftar di sini');
});
