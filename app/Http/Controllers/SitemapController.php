<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Seo\BuildArticlesSitemap;
use App\Actions\Seo\BuildPagesSitemap;
use App\Actions\Seo\BuildPhonesSitemap;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\SitemapIndex;

class SitemapController extends Controller
{
    public function index(): Response
    {
        return $this->xml('index', fn (): string => SitemapIndex::create()
            ->add(route('sitemap.pages'))
            ->add(route('sitemap.phones'))
            ->add(route('sitemap.articles'))
            ->render());
    }

    public function pages(BuildPagesSitemap $sitemap): Response
    {
        return $this->xml('pages', fn (): string => $sitemap->handle()->render());
    }

    public function phones(BuildPhonesSitemap $sitemap): Response
    {
        return $this->xml('phones', fn (): string => $sitemap->handle()->render());
    }

    public function articles(BuildArticlesSitemap $sitemap): Response
    {
        return $this->xml('articles', fn (): string => $sitemap->handle()->render());
    }

    /** @param  callable(): string  $render */
    private function xml(string $key, callable $render): Response
    {
        $xml = Cache::remember(
            'sitemap:'.$key,
            (int) config('seo.sitemap.cache_ttl'),
            $render,
        );

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
