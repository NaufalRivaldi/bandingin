<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Support\FaqRepository;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Pusat bantuan — Bandingin.id')]
class FaqPage extends Component
{
    #[Url(as: 'topik')]
    public string $topic = FaqRepository::ALL_TOPICS;

    /** Index of the open accordion item within the filtered list; -1 when all are closed. */
    public int $open = 0;

    /** @return list<array{topic: string, question: string, answer: string}> */
    #[Computed]
    public function faqs(): array
    {
        return FaqRepository::forTopic($this->topic);
    }

    public function selectTopic(string $topic): void
    {
        $this->topic = $topic;
        $this->open = 0;
    }

    public function toggle(int $index): void
    {
        $this->open = $this->open === $index ? -1 : $index;
    }

    public function render(): View
    {
        return view('livewire.faq-page', [
            'steps' => FaqRepository::steps(),
            'topics' => FaqRepository::topics(),
        ]);
    }
}
