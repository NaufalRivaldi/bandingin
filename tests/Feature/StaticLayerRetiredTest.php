<?php

declare(strict_types=1);

/*
 * The public site reads the database directly. These assertions keep the
 * retired static catalogue layer from creeping back in.
 */

arch('the static catalogue layer is gone')
    ->expect('App\Support')
    ->toBeUsedIn([]);

arch('the catalogue dtos are gone')
    ->expect('App\Data')
    ->toBeUsedIn([]);

arch('only the render action bridges the public site to filament')
    ->expect('App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\PhoneComparisonBlock')
    ->toOnlyBeUsedIn([
        'App\Actions\RenderArticleBody',
        'App\Filament\Resources\Articles\Schemas\ArticleForm',
    ]);
