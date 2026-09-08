<?php

declare(strict_types=1);

use App\Livewire\FaqPage;
use App\Models\Faq;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

it('lists every faq by default', function (): void {
    Livewire::test(FaqPage::class)
        ->assertSet('topic', FaqPage::ALL_TOPICS)
        ->assertSee(Faq::orderBy('sort')->value('question'));
});

it('filters by topic', function (): void {
    $component = Livewire::test(FaqPage::class)->call('selectTopic', 'Harga')->instance();

    expect($component->faqs()->pluck('topic')->unique()->pluck('value')->all())
        ->toBe(['Harga']);
});

it('keeps the faqs in their curated order', function (): void {
    $faqs = Livewire::test(FaqPage::class)->instance()->faqs();

    expect($faqs->pluck('sort')->all())->toBe($faqs->pluck('sort')->sort()->values()->all());
});

it('opens one answer at a time', function (): void {
    Livewire::test(FaqPage::class)
        ->assertSet('open', 0)
        ->call('toggle', 2)
        ->assertSet('open', 2)
        ->call('toggle', 2)
        ->assertSet('open', -1);
});

it('resets the open item when the topic changes', function (): void {
    Livewire::test(FaqPage::class)
        ->call('toggle', 3)
        ->call('selectTopic', 'Skor')
        ->assertSet('open', 0);
});
