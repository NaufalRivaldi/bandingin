@props([
    'label',
    'value',
])

<div {{ $attributes->merge(['class' => 'rounded-[18px] border border-line bg-surface p-[18px]']) }}>
    <div class="font-mono text-[10px] tracking-[.12em] text-muted uppercase">{{ $label }}</div>
    <div class="mt-2 font-display text-[clamp(18px,2.1vw,24px)] font-extrabold tracking-[-.025em]">{{ $value }}</div>
</div>
