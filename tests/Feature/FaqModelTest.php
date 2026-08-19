<?php

declare(strict_types=1);

use App\Enums\FaqTopic;
use App\Models\Faq;

it('casts the topic to an enum', function (): void {
    $faq = Faq::factory()->create(['topic' => FaqTopic::KartuDuel]);

    expect($faq->fresh()->topic)->toBe(FaqTopic::KartuDuel);
});

it('lists the four frontend topics', function (): void {
    expect(FaqTopic::values())->toBe(['Kartu Duel', 'Harga', 'Skor', 'Data']);
});

it('filters by topic', function (): void {
    Faq::factory()->create(['topic' => FaqTopic::Harga, 'question' => 'Seberapa sering harga diperbarui?']);
    Faq::factory()->create(['topic' => FaqTopic::Skor, 'question' => 'Bagaimana skor dihitung?']);

    expect(Faq::where('topic', FaqTopic::Harga)->pluck('question')->all())
        ->toBe(['Seberapa sering harga diperbarui?']);
});
