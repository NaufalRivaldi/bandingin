@props([
    'pros' => [],
    'cons' => [],
    'prosLabel' => 'Kelebihan',
    'consLabel' => 'Kekurangan',
    'boxed' => true,
])

@php
    $panel = $boxed ? 'rounded-[20px] border border-line bg-surface p-[22px]' : '';
@endphp

<div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,260px),1fr))] gap-[18px]">
    <div class="{{ $panel }}">
        <div class="text-[13px] font-semibold text-muted">{{ $prosLabel }}</div>
        <div class="mt-3.5 flex flex-col gap-2.5">
            @foreach ($pros as $item)
                <div class="flex gap-2.5 text-sm leading-relaxed">
                    <span class="flex-none font-mono text-win">+</span>
                    <span>{{ $item }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="{{ $panel }}">
        <div class="text-[13px] font-semibold text-muted">{{ $consLabel }}</div>
        <div class="mt-3.5 flex flex-col gap-2.5">
            @foreach ($cons as $item)
                <div class="flex gap-2.5 text-sm leading-relaxed">
                    <span class="flex-none font-mono text-lose">−</span>
                    <span>{{ $item }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
