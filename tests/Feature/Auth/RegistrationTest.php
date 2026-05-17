<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'formatted_address' => 'Victoria de Durango, Durango, México',
        'lat' => 24.0248409,
        'lng' => -104.6608131,
        'place_id' => 'test-place-id',
        'password' => 'Zx9!vQ2@mL7#pR4$',
        'password_confirmation' => 'Zx9!vQ2@mL7#pR4$',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});