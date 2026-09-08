<?php

declare(strict_types=1);

/*
 * The public site reads the database directly. These assertions keep the retired
 * static catalogue layer from creeping back in.
 *
 * Note: an arch `expect('App\Support')->toBeUsedIn([])` is NOT used here — Pest
 * skips that expectation when the namespace has no files, so it would pass
 * vacuously and never catch a reintroduction. These check the filesystem instead.
 */

it('has deleted the static catalogue directories', function (): void {
    expect(is_dir(app_path('Support')))->toBeFalse()
        ->and(is_dir(app_path('Data')))->toBeFalse();
});

it('has no source file referencing the retired namespaces', function (): void {
    $roots = [app_path(), database_path(), resource_path()];
    $offenders = [];

    foreach ($roots as $root) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if (! $file->isFile() || ! in_array($file->getExtension(), ['php'], true)) {
                continue;
            }

            if (preg_match('/App\\\\\\\\(Support|Data)\\\\\\\\/', (string) file_get_contents($file->getPathname()))) {
                $offenders[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }
    }

    expect($offenders)->toBeEmpty();
});

arch('only the render action bridges the public site to filament')
    ->expect('App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\PhoneComparisonBlock')
    ->toOnlyBeUsedIn([
        'App\Actions\RenderArticleBody',
        'App\Filament\Resources\Articles\Schemas\ArticleForm',
    ]);
