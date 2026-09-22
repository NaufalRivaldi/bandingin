<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\News\FetchArticleContent;
use App\Actions\News\FetchFeed;
use App\Actions\News\ImportScrapedArticles;
use App\Models\ScrapeSource;
use App\News\ScrapedArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportNews extends Command
{
    protected $signature = 'news:import';

    protected $description = 'Import articles from every active scrape source';

    public function handle(
        FetchFeed $fetchFeed,
        FetchArticleContent $fetchContent,
        ImportScrapedArticles $import,
    ): int {
        $created = 0;
        $failed = [];

        foreach (ScrapeSource::active()->get() as $source) {
            try {
                $articles = $fetchFeed->handle($source);
            } catch (Throwable $exception) {
                $failed[] = $source->name;
                $source->markRan($exception->getMessage());

                Log::warning('scrape source failed', [
                    'source' => $source->name,
                    'message' => $exception->getMessage(),
                ]);

                $this->components->error("{$source->name}: {$exception->getMessage()}");

                continue;
            }

            $created += $import->handle($this->withContent($articles, $import, $fetchContent), $source);
            $source->markRan();

            $this->components->info("{$source->name}: ".count($articles).' item diperiksa.');
        }

        $this->components->info("Selesai. {$created} artikel baru disimpan.");

        if ($failed !== []) {
            $this->components->error('Sumber gagal: '.implode(', ', $failed));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Only genuinely new articles get a page fetch. In steady state that is one
     * or two extra requests per source rather than ten.
     *
     * @param  list<ScrapedArticle>  $articles
     * @return list<ScrapedArticle>
     */
    private function withContent(array $articles, ImportScrapedArticles $import, FetchArticleContent $fetchContent): array
    {
        $known = $import->existingIds($articles);

        return array_map(
            function (ScrapedArticle $article) use ($known, $fetchContent): ScrapedArticle {
                if (in_array($article->externalId, $known, true)) {
                    return $article;
                }

                $content = $fetchContent->handle($article->url);

                return $article->withContent($content?->body, $content?->imageUrl);
            },
            $articles,
        );
    }
}
