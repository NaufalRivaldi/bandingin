<?php

declare(strict_types=1);

namespace App\Data;

final readonly class Article
{
    /**
     * @param  list<string>  $body  Paragraphs shown before the embedded duel table.
     * @param  list<string>  $bodyRest  Paragraphs shown after it.
     * @param  list<string>  $tags
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $category,
        public string $date,
        public string $dateLong,
        public int $readMinutes,
        public string $title,
        public string $excerpt,
        public array $body,
        public string $quote,
        public array $bodyRest,
        public array $tags,
        public ?ArticleSpecTable $specs = null,
    ) {}

    public function meta(): string
    {
        return $this->category.' · '.$this->date;
    }
}
