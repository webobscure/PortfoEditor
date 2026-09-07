<?php

declare(strict_types=1);

use App\Models\User;

it('registers a user and starts a session', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Alex Morgan',
        'email' => 'alex@example.com',
        'password' => 'correct horse 9',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'alex@example.com')
        ->assertJsonPath('data.initials', 'AM');

    $this->assertAuthenticated();
    expect(User::where('email', 'alex@example.com')->exists())->toBeTrue();
});

it('rejects a weak or duplicate registration', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/register', [
        'name' => 'A',
        'email' => 'taken@example.com',
        'password' => 'short',
    ])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
});

it('logs a user in and out', function () {
    $user = User::factory()->create(['password' => bcrypt('secret-passw0rd')]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'secret-passw0rd',
    ])->assertOk()->assertJsonPath('data.id', $user->id);

    $this->assertAuthenticated();

    $this->postJson('/api/logout')->assertOk();

    // The guard resolved during the login request caches the user for the rest
    // of the test process, so it is dropped before asking whether a fresh
    // request is still authenticated.
    $this->app['auth']->forgetGuards();
    $this->flushSession();

    $this->getJson('/api/user')->assertUnauthorized();
});

it('rejects bad credentials without leaking which field was wrong', function () {
    $user = User::factory()->create(['password' => bcrypt('secret-passw0rd')]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('refuses the user endpoint when signed out', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});
