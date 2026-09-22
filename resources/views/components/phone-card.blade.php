@props([
    'phone',
    'selected' => false,
    'chips' => null,
])

@php
    /** @var \App\Models\Phone $phone */
    $chips ??= [
        $phone->display('chipset'),
        $phone->display('ram'),
        $phone->display('baterai'),
    ];
@endphp

<div @class([
    'rounded-[20px] border bg-white p-4 transition-[border-color,box-shadow] duration-200',
    'border-brand shadow-[0_0_0_3px_rgba(37,99,235,.12)]' => $selected,
    'border-line' => ! $selected,
])>
    <div class="relative grid aspect-4/3 place-items-center rounded-[15px] border border-line-soft bg-[linear-gradient(160deg,#f4f6fb,#e4eaf5)]">
        <x-phone-photo :phone="$phone" mock-class="h-[74%] w-[30%]" />

        @if ($phone->tag !== '')
            <span class="absolute top-2.5 left-2.5 rounded-full bg-ink px-2.5 py-1 font-sans text-[10.5px] font-semibold text-white">{{ $phone->tag }}</span>
        @endif
    </div>

    <div class="mt-3.5 font-mono text-[10px] tracking-[.12em] text-muted uppercase">{{ $phone->brand->name }}</div>

    <a
        href="{{ route('hp.show', $phone->slug) }}"
        class="mt-[5px] block font-display text-[16.5px] leading-tight font-extrabold tracking-[-.02em] text-ink hover:text-brand"
    >{{ $phone->name }}</a>

    <div class="mt-[7px] font-mono text-[14.5px] font-medium">{{ $phone->formattedPrice() }}</div>

    <div class="mt-2.5 flex flex-wrap gap-1.5">
        @foreach ($chips as $chip)
            <span class="rounded-[7px] border border-line px-2 py-1 text-[10.5px] font-medium text-muted">{{ $chip }}</span>
        @endforeach
    </div>

    {{ $slot }}
</div>
