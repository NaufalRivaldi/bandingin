<?php

declare(strict_types=1);

return [

    'site_name' => 'Bandingin.id',

    'description' => 'Adu spesifikasi, harga, dan performa dua HP berdampingan. Kami tandai mana yang menang di tiap baris.',

    'separator' => ' — ',

    /*
     * Identity used by the Organization / publisher JSON-LD nodes.
     */
    'author' => 'Rizky Adhitama',

    'organization' => [
        'name' => 'Bandingin.id',
        'logo' => env('SEO_ORGANIZATION_LOGO'),
    ],

    /*
     * Social card defaults. The image stays null until a real OG asset exists;
     * no image tag is emitted while it is null.
     */
    'social' => [
        'image' => env('SEO_SOCIAL_IMAGE'),
        'twitter_site' => env('SEO_TWITTER_SITE'),
    ],

    'robots' => [

        'default' => 'index, follow',

        'noindex' => 'noindex, follow',

        /*
         * Crawlers that are explicitly welcomed in robots.txt, including the
         * AI crawlers, so a blanket rule can never be read as excluding them.
         */
        'good_bots' => [
            'Googlebot',
            'Googlebot-Image',
            'Googlebot-News',
            'Google-Extended',
            'Bingbot',
            'DuckDuckBot',
            'Applebot',
            'Applebot-Extended',
            'YandexBot',
            'GPTBot',
            'OAI-SearchBot',
            'ChatGPT-User',
            'ClaudeBot',
            'Claude-User',
            'Claude-SearchBot',
            'anthropic-ai',
            'PerplexityBot',
            'Perplexity-User',
            'Gemini-Deep-Research',
            'meta-externalagent',
            'Bytespider',
            'CCBot',
        ],

        /*
         * Commercial SEO and content scrapers. They cost bandwidth and return
         * nothing, so robots.txt disallows them outright.
         */
        'bad_bots' => [
            'AhrefsBot',
            'SemrushBot',
            'MJ12bot',
            'DotBot',
            'BLEXBot',
            'PetalBot',
            'DataForSeoBot',
            'SeekportBot',
            'MegaIndex',
            'ZoominfoBot',
            'Barkrowler',
            'SerpstatBot',
            'magpie-crawler',
            'Screaming Frog SEO Spider',
        ],

        /*
         * Paths no crawler should spend budget on.
         */
        'disallowed_paths' => [
            '/admin',
            '/livewire',
            '/up',
        ],
    ],

    'sitemap' => [
        'cache_ttl' => (int) env('SEO_SITEMAP_CACHE_TTL', 3600),
    ],

];
