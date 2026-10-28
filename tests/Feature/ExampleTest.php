<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response
        ->assertStatus(200)
        ->assertSee('Masuk Akun')
        ->assertSee('SISTEM ABSENSI PT FOLLOW ME')
        ->assertSee('Kerja lebih')
        ->assertDontSee('Learn more');
});
