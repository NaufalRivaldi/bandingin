@props([
    'on' => false,
    'label' => '',
])

<button
    type="button"
    role="switch"
    aria-checked="{{ $on ? 'true' : 'false' }}"
    {{ $attributes->merge(['class' => 'inline-flex cursor-pointer items-center gap-2.5 rounded-full border border-line bg-white py-2 pr-3.5 pl-2.5 font-sans text-[13px] font-medium text-ink']) }}
>
    <span @class([
        'relative block h-[22px] w-[38px] flex-none rounded-full transition-colors duration-200',
        'bg-brand' => $on,
        'bg-line' => ! $on,
    ])>
        <span @class([
            'absolute top-[3px] left-[3px] size-4 rounded-full bg-white shadow-[0_1px_3px_rgba(12,20,36,.3)] transition-transform duration-200',
            'translate-x-4' => $on,
        ])></span>
    </span>
    {{ $label ?: $slot }}
</button>
