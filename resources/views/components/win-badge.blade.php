@props(['delay' => null])

<span
    @style(["animation-delay: {$delay}s" => $delay !== null])
    {{ $attributes->merge(['class' => 'anim-badge-pop ml-2 inline-flex items-center rounded-full bg-win px-2 py-0.5 align-[2px] font-sans text-[10px] font-semibold tracking-[.02em] whitespace-nowrap text-white']) }}
>Unggul</span>
