<?php

declare(strict_types=1);

use App\Actions\RenderArticleBody;
use App\Models\Article;
use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;
use Illuminate\Support\HtmlString;

it('passes plain html through unchanged', function (): void {
    $article = Article::factory()->create([
        'body' => '<p>Paragraf pertama.</p><p>Paragraf kedua.</p>',
    ]);

    $html = (string) app(RenderArticleBody::class)->handle($article);

    expect($html)->toContain('Paragraf pertama')
        ->and($html)->toContain('Paragraf kedua');
});

it('returns an htmlstring so blade can echo it', function (): void {
    $article = Article::factory()->create(['body' => '<p>Halo.</p>']);

    expect(app(RenderArticleBody::class)->handle($article))
        ->toBeInstanceOf(HtmlString::class);
});

it('renders an empty body without failing', function (): void {
    $article = Article::factory()->create(['body' => '']);

    expect((string) app(RenderArticleBody::class)->handle($article))->toBeString();
});

it('expands an embedded comparison block from live phone data', function (): void {
    $definition = SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas']);
    $left = Phone::factory()->create(['name' => 'Galaxy A56 5G']);
    $right = Phone::factory()->create(['name' => 'Redmi Note 14 Pro 5G']);

    foreach ([[$left, '5.000 mAh', 5000], [$right, '5.500 mAh', 5500]] as [$phone, $display, $comparable]) {
        PhoneSpec::factory()->create([
            'phone_id' => $phone->id,
            'spec_definition_id' => $definition->id,
            'display_value' => $display,
            'comparable_value' => $comparable,
        ]);
    }

    $body = json_encode([
        'type' => 'doc',
        'content' => [[
            'type' => 'customBlock',
            'attrs' => [
                'id' => 'phone_comparison',
                'config' => [
                    'leftPhoneId' => $left->id,
                    'rightPhoneId' => $right->id,
                    'specKeys' => ['baterai'],
                ],
            ],
        ]],
    ]);

    $article = Article::factory()->create(['body' => $body]);

    $html = (string) app(RenderArticleBody::class)->handle($article);

    expect($html)->toContain('Galaxy A56 5G')
        ->and($html)->toContain('5.500 mAh');
});
