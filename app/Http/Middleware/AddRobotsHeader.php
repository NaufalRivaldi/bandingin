<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Artesaos\SEOTools\Facades\SEOMeta;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mirrors the robots meta tag onto an X-Robots-Tag header.
 *
 * The directive is resolved once while the page renders, so the header and the
 * meta tag can never disagree.
 */
class AddRobotsHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $robots = SEOMeta::getRobots();

        if (filled($robots)) {
            $response->headers->set('X-Robots-Tag', $robots);
        }

        return $response;
    }
}
