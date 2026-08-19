<?php

declare(strict_types=1);

use App\Livewire\NewsletterSubscribe;
use App\Models\NewsletterSubscriber;
use Livewire\Livewire;

it('stores the address and confirms the subscription', function (): void {
    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'pembaca@bandingin.test')
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('subscribed', true)
        ->assertSet('email', '')
        ->assertSee('Siap! Kami kabari kalau ada harga turun.');

    expect(NewsletterSubscriber::whereEmail('pembaca@bandingin.test')->exists())->toBeTrue();
});

it('rejects an invalid address without storing anything', function (): void {
    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'bukan-email')
        ->call('subscribe')
        ->assertHasErrors(['email' => 'email'])
        ->assertSet('subscribed', false);

    expect(NewsletterSubscriber::count())->toBe(0);
});

it('requires an address', function (): void {
    Livewire::test(NewsletterSubscribe::class)
        ->call('subscribe')
        ->assertHasErrors(['email' => 'required']);
});

it('does not duplicate an address that already subscribed', function (): void {
    NewsletterSubscriber::create(['email' => 'pembaca@bandingin.test']);

    Livewire::test(NewsletterSubscribe::class)
        ->set('email', 'pembaca@bandingin.test')
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('subscribed', true);

    expect(NewsletterSubscriber::whereEmail('pembaca@bandingin.test')->count())->toBe(1);
});
