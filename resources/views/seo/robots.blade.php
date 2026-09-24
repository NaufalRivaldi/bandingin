@foreach ($badBots as $bot)
User-agent: {{ $bot }}
@endforeach
Disallow: /

@foreach ($goodBots as $bot)
User-agent: {{ $bot }}
@endforeach
Allow: /
@foreach ($disallowedPaths as $path)
Disallow: {{ $path }}
@endforeach

User-agent: *
Allow: /
@foreach ($disallowedPaths as $path)
Disallow: {{ $path }}
@endforeach

Sitemap: {{ $sitemap }}
