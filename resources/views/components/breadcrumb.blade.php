@props([
    /** @var list<array{label: string, url?: string}> */
    'items' => [],
])

<div class="flex min-w-0 items-center gap-2 font-mono text-[11px] text-muted">
    <a href="{{ route('home') }}" class="flex-none whitespace-nowrap text-muted hover:text-brand">Beranda</a>

    @foreach ($items as $item)
        <span class="flex-none">/</span>

        @if (isset($item['url']))
            <a href="{{ $item['url'] }}" class="flex-none whitespace-nowrap text-muted hover:text-brand">{{ $item['label'] }}</a>
        @elseif ($loop->last)
            <span class="min-w-0 truncate text-ink">{{ $item['label'] }}</span>
        @else
            <span class="flex-none whitespace-nowrap">{{ $item['label'] }}</span>
        @endif
    @endforeach
</div>
