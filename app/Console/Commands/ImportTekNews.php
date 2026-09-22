<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\News\FetchArticleImage;
use App\Actions\News\FetchTekChannel;
use App\Actions\News\ImportScrapedArticles;
use App\News\ScrapedArticle;
use App\News\TekChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportTekNews extends Command
{
    protected $signature = 'news:import-tek';

    protected $description = 'Import article metadata from the tek.id RSS channels';

    public function handle(
        FetchTekChannel $fetchChannel,
        FetchArticleImage $fetchImage,
        ImportScrapedArticles $import,
    ): int {
        $created = 0;
        $failed = [];

        foreach (TekChannel::cases() as $channel) {
            try {
                $articles = $fetchChannel->handle($channel);
            } catch (Throwable $exception) {
                $failed[] = $channel->value;

                Log::warning('tek.id channel import failed', [
                    'channel' => $channel->value,
                    'message' => $exception->getMessage(),
                ]);

                $this->components->error("{$channel->value}: {$exception->getMessage()}");

                continue;
            }

            $created += $import->handle($this->withImages($articles, $import, $fetchImage));

            $this->components->info("{$channel->value}: ".count($articles).' item diperiksa.');
        }

        $this->components->info("Selesai. {$created} item baru disimpan.");

        if ($failed !== []) {
            $this->components->error('Kanal gagal: '.implode(', ', $failed));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Only genuinely new articles get an image fetch. In steady state this is one
     * or two extra requests per channel rather than ten.
     *
     * @param  list<ScrapedArticle>  $articles
     * @return list<ScrapedArticle>
     */
    private function withImages(array $articles, ImportScrapedArticles $import, FetchArticleImage $fetchImage): array
    {
        $known = $import->existingIds($articles);

        return array_map(
            fn (ScrapedArticle $article): ScrapedArticle => in_array($article->externalId, $known, true)
                ? $article
                : $article->withImage($fetchImage->handle($article->url)),
            $articles,
        );
    }
}
