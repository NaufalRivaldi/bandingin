<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests away from the panel', function (): void {
    $this->get('/admin')->assertRedirect();
});

it('lets an authenticated user reach the panel', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertSuccessful();
});
