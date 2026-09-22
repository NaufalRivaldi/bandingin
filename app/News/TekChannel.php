<?php

declare(strict_types=1);

namespace App\News;

/**
 * The seven public channels on tek.id. The case value is the display label and
 * the stored `news_items.channel` value; the lowercased name is the URL segment.
 */
enum TekChannel: string
{
    case Tek = 'Tek';
    case Gadget = 'Gadget';
    case Review = 'Review';
    case Culture = 'Culture';
    case Future = 'Future';
    case Insight = 'Insight';
    case Tektalk = 'Tektalk';

    public function path(): string
    {
        return mb_strtolower($this->value);
    }

    public function feedUrl(): string
    {
        return rtrim((string) config('services.tek.base_url'), '/').'/'.$this->path().'/rss';
    }
}
