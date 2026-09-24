<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        return response()
            ->view('seo.robots', [
                'goodBots' => config('seo.robots.good_bots'),
                'badBots' => config('seo.robots.bad_bots'),
                'disallowedPaths' => config('seo.robots.disallowed_paths'),
                'sitemap' => route('sitemap.index'),
            ])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
