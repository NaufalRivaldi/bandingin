<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class NewsletterSubscribe extends Component
{
    #[Validate('required|email|max:255')]
    public string $email = '';

    public bool $subscribed = false;

    public function subscribe(): void
    {
        $this->email = Str::lower(trim($this->email));

        $this->validate();

        NewsletterSubscriber::firstOrCreate(['email' => $this->email]);

        $this->reset('email');
        $this->subscribed = true;
    }

    public function subscribeAnother(): void
    {
        $this->reset();
    }

    public function render(): View
    {
        return view('livewire.newsletter-subscribe');
    }
}
