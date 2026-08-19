@props([
    'href' => '#',
    'label' => 'Cek Harga',
    'arrow' => true,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-[5px] rounded-full border border-brand/22 bg-brand/8 px-3 py-1.5 font-sans text-xs font-semibold whitespace-nowrap text-brand hover:bg-brand/14 hover:text-brand-dark']) }}
>
    {{ $label }}
    @if ($arrow)
        <span class="font-mono text-[11px]">→</span>
    @endif
</a>
