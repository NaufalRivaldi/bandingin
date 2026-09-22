@php
    $fieldId = 'newsletter-email-'.$this->getId();
@endphp

<div>
    <div class="font-display text-base leading-tight font-extrabold tracking-[-.02em]">Dapat info harga turun duluan.</div>

    @if ($subscribed)
        <div class="mt-3.5 flex items-start gap-2.5 rounded-[11px] border border-brand/30 bg-brand/8 px-[13px] py-3">
            <span class="grid size-5 flex-none place-items-center rounded-full bg-brand font-mono text-[10px] font-semibold text-white">✓</span>

            <div>
                <div class="text-sm font-semibold text-ink">Siap! Kami kabari kalau ada harga turun.</div>

                <button
                    type="button"
                    wire:click="subscribeAnother"
                    class="mt-1 cursor-pointer border-0 bg-transparent p-0 text-[11.5px] font-medium text-brand hover:underline"
                >Daftarkan email lain</button>
            </div>
        </div>
    @else
        <form wire:submit="subscribe" class="mt-3.5 flex flex-wrap gap-2">
            <label for="{{ $fieldId }}" class="sr-only">Alamat email</label>

            <input
                id="{{ $fieldId }}"
                type="email"
                wire:model="email"
                placeholder="Alamat email"
                class="min-h-[46px] min-w-0 flex-[1_1_140px] rounded-[11px] border border-line bg-white px-[13px] py-3 font-sans text-sm text-ink"
            />

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="subscribe"
                class="min-h-[46px] flex-none cursor-pointer rounded-[11px] bg-brand px-[18px] py-3 font-sans text-sm font-semibold text-white hover:bg-brand-dark disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="subscribe">Langganan</span>
                <span wire:loading wire:target="subscribe">Mengirim…</span>
            </button>
        </form>

        @error('email')
            <div class="mt-2 text-[11.5px] leading-[1.5] text-lose">{{ $message }}</div>
        @enderror

        <div class="mt-2.5 text-[11.5px] leading-[1.5] text-muted">Satu email per minggu. Bisa berhenti kapan saja.</div>
    @endif
</div>
