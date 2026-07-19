<?php

test('public registration screen is disabled', function () {
    $this->get('/register')->assertNotFound();
});

test('public users cannot self-register', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
});
