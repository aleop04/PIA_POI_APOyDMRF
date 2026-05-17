<?php

use App\Models\User;
use App\Notifications\ResetPasswordCustomNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, ResetPasswordCustomNotification::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo(
        $user,
        ResetPasswordCustomNotification::class,
        function ($notification) use ($user) {
            $response = $this->get('/reset-password/'.$notification->token.'?email='.$user->email);

            $response->assertOk();

            return true;
        }
    );
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo(
        $user,
        ResetPasswordCustomNotification::class,
        function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'Zx9!vQ2@mL7#pR4$',
                'password_confirmation' => 'Zx9!vQ2@mL7#pR4$',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            expect(Hash::check('Zx9!vQ2@mL7#pR4$', $user->refresh()->password))->toBeTrue();

            return true;
        }
    );
});

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'Zx9!vQ2@mL7#pR4$',
        'password_confirmation' => 'Zx9!vQ2@mL7#pR4$',
    ]);

    $response->assertSessionHasErrors('email');
});