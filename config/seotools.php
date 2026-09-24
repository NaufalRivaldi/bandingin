<?php

declare(strict_types=1);

/**
 * @see https://github.com/artesaos/seotools
 */
return [
    'inertia' => false,
    'meta' => [
        'defaults' => [
            'title' => config('seo.site_name'),
            'titleBefore' => false,
            'description' => config('seo.description'),
            'separator' => config('seo.separator'),
            'keywords' => [],

            /*
             * 'current' uses Url::current(), which drops the query string, so a
             * filtered catalogue URL canonicalises to the clean path for free.
             */
            'canonical' => 'current',
            'robots' => config('seo.robots.default'),
        ],
        'webmaster_tags' => [
            'google' => null,
            'bing' => null,
            'alexa' => null,
            'pinterest' => null,
            'yandex' => null,
            'norton' => null,
        ],
        'add_notranslate_class' => false,
    ],
    'opengraph' => [
        'defaults' => [
            'title' => config('seo.site_name'),
            'description' => config('seo.description'),
            'url' => null,
            'type' => 'website',
            'site_name' => config('seo.site_name'),
            'locale' => 'id_ID',
            'images' => array_filter([config('seo.social.image')]),
        ],
    ],
    'twitter' => [
        'defaults' => array_filter([
            'card' => 'summary_large_image',
            'site' => config('seo.social.twitter_site'),
        ]),
    ],
    'json-ld' => [
        'defaults' => [
            'title' => config('seo.site_name'),
            'description' => config('seo.description'),
            'url' => null,
            'type' => 'WebPage',
            'images' => array_filter([config('seo.social.image')]),
        ],
    ],
];
