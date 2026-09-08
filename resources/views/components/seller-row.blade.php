@props([
    'seller',
    'compact' => false,
    'cheapest' => false,
])

@php
    /** @var \App\Models\PhoneListing $seller */
@endphp

<div class="flex flex-wrap items-center gap-x-3.5 gap-y-2.5 border-b border-line-soft px-[clamp(14px,2.2vw,18px)] py-3.5 last:border-b-0">
    <span class="min-w-0 flex-1 {{ $compact ? '' : 'basis-[180px]' }}">
        <span class="block {{ $compact ? 'text-sm font-medium' : 'text-[15px] font-semibold' }}">{{ $seller->store->name }}</span>
        <span class="mt-[3px] block text-[{{ $compact ? '11.5px' : '12px' }}] text-muted">{{ $seller->note }}</span>
    </span>

    @if ($cheapest)
        <span class="inline-flex items-center rounded-full border border-win/28 bg-win/12 px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap text-win-ink">Termurah</span>
    @endif

    @if (filled($seller->warranty_tag))
        <span class="inline-flex items-center rounded-full border border-line bg-surface px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap text-muted">{{ $seller->warranty_tag }}</span>
    @endif

    <span class="font-mono {{ $compact ? 'text-sm font-medium' : 'text-base font-semibold' }} whitespace-nowrap">{{ $seller->formattedPrice() }}</span>

    <x-affiliate-link
        :label="$compact ? 'Cek' : 'Cek Harga'"
        :arrow="false"
        :href="$seller->product_url"
        class="flex-none {{ $compact ? 'px-3.5 py-2.5' : 'px-[18px] py-[11px] text-[13px]' }}"
    />
</div>
