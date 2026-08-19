<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FaqTopic;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    protected $fillable = [
        'topic',
        'question',
        'answer',
        'sort',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'topic' => FaqTopic::class,
        ];
    }
}
