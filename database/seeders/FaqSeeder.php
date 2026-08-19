<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FaqTopic;
use App\Models\Faq;
use App\Support\FaqRepository;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach (FaqRepository::all() as $sort => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                [
                    'topic' => FaqTopic::from($faq['topic']),
                    'answer' => $faq['answer'],
                    'sort' => $sort + 1,
                ],
            );
        }
    }
}
