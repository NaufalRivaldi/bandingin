<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Livewire\BeritaPage;
use App\Models\Article;
use Livewire\Livewire;

it('renders the berita page when there are no articles', function (): void {
    $this->get(route('berita'))->assertSuccessful();
});

it('hides the featured hero when there is nothing to feature', function (): void {
    Livewire::test(BeritaPage::class)
        ->assertSet('category', BeritaPage::ALL_CATEGORIES)
        ->assertDontSee('Sorotan');
});

it('explains the empty list instead of showing a bare zero', function (): void {
    $this->get(route('berita'))
        ->assertSuccessful()
        ->assertSee('Belum ada artikel')
        ->assertSee('0 artikel');
});

it('renders a category with no articles', function (): void {
    seedCatalog();
    Article::query()->update(['category' => ArticleCategory::Rilis->value]);

    Livewire::test(BeritaPage::class)
        ->set('category', ArticleCategory::Panduan->value)
        ->assertSuccessful()
        ->assertSee('Belum ada artikel');
});

it('still shows the featured hero once articles exist', function (): void {
    seedCatalog();

    $this->get(route('berita'))
        ->assertSuccessful()
        ->assertSee('Sorotan')
        ->assertDontSee('Belum ada artikel');
});
