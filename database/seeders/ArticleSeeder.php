<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Article as ArticleData;
use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Support\ArticleRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Ports App\Support\ArticleRepository, folding the design's three prose fields
 * (body, quote, bodyRest) into the single rich-text body the admin will edit.
 */
class ArticleSeeder extends Seeder
{
    /** Indonesian month names as they appear in the DTO's "dateLong" field. */
    private const array MONTHS = [
        'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
        'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
        'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
    ];

    public function run(): void
    {
        foreach (ArticleRepository::all() as $data) {
            Article::updateOrCreate(
                ['slug' => $data->slug],
                [
                    'title' => $data->title,
                    'category' => ArticleCategory::from($data->category),
                    'excerpt' => $data->excerpt,
                    'body' => $this->toHtml($data),
                    'tags' => $data->tags,
                    'read_minutes' => $data->readMinutes,
                    'published_at' => $this->parsePublishedAt($data->dateLong),
                ],
            );
        }
    }

    /** "12 Agustus 2026" becomes 2026-08-12. */
    private function parsePublishedAt(string $dateLong): Carbon
    {
        [$day, $month, $year] = explode(' ', $dateLong);

        return Carbon::create((int) $year, self::MONTHS[$month], (int) $day)->startOfDay();
    }

    private function toHtml(ArticleData $data): string
    {
        $html = $this->paragraphs($data->body);

        if ($data->quote !== '') {
            $html .= '<blockquote><p>'.e($data->quote).'</p></blockquote>';
        }

        $html .= $this->paragraphs($data->bodyRest);

        if ($data->specs !== null) {
            $html .= $this->specTable($data);
        }

        return $html;
    }

    /** @param  list<string>  $prose */
    private function paragraphs(array $prose): string
    {
        return collect($prose)
            ->filter(fn (string $paragraph): bool => trim($paragraph) !== '')
            ->map(fn (string $paragraph): string => '<p>'.e($paragraph).'</p>')
            ->implode('');
    }

    /** A stopgap until the Filament RichEditor comparison block lands in Part 2. */
    private function specTable(ArticleData $data): string
    {
        $table = $data->specs;

        $rows = collect($table->rows)
            ->map(fn (array $row): string => '<tr><th>'.e($row['label']).'</th>'
                .'<td>'.e($row['left']).'</td>'
                .'<td>'.e($row['right']).'</td></tr>')
            ->implode('');

        return '<h3>'.e($table->title).'</h3>'
            .'<table><thead><tr><th></th><th>'.e($table->left).'</th>'
            .'<th>'.e($table->right).'</th></tr></thead>'
            .'<tbody>'.$rows.'</tbody></table>';
    }
}
