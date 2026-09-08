<?php

declare(strict_types=1);

namespace App\Actions;

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\PhoneComparisonBlock;
use App\Models\Article;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\HtmlString;

/**
 * The only place the public site touches Filament.
 *
 * Article bodies are authored in the admin RichEditor and may embed a phone
 * comparison block, which has to be expanded from live catalogue data at read
 * time. Keeping that dependency in one Action lets the Livewire pages stay free
 * of Filament imports, which tests/Feature/Filament/AdminBoundaryTest.php asserts.
 */
final class RenderArticleBody
{
    public function handle(Article $article): HtmlString
    {
        $body = $article->body;

        if ($body === null || $body === '') {
            return new HtmlString('');
        }

        return new HtmlString(
            RichContentRenderer::make($body)
                ->customBlocks([PhoneComparisonBlock::class])
                ->toHtml(),
        );
    }
}
