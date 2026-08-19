<div>
    <div class="font-display text-base leading-tight font-extrabold tracking-[-.02em]">Dapat info harga turun duluan.</div>

    <form wire:submit="subscribe" class="mt-3.5 flex flex-wrap gap-2">
        <label for="footer-email" class="sr-only">Alamat email</label>

        <input
            id="footer-email"
            type="email"
            wire:model="email"
            placeholder="Alamat email"
            class="min-h-[46px] min-w-0 flex-[1_1_140px] rounded-[11px] border border-line bg-white px-[13px] py-3 font-sans text-sm text-ink"
        />

        <button
            type="submit"
            class="min-h-[46px] flex-none cursor-pointer rounded-[11px] bg-brand px-[18px] py-3 font-sans text-sm font-semibold text-white hover:bg-brand-dark"
        >Langganan</button>
    </form>

    @error('email')
        <div class="mt-2 text-[11.5px] leading-[1.5] text-lose">{{ $message }}</div>
    @enderror

    <div class="mt-2.5 text-[11.5px] leading-[1.5] text-muted">
        {{ $subscribed ? 'Siap! Kami kabari kalau ada harga turun.' : 'Satu email per minggu. Bisa berhenti kapan saja.' }}
    </div>
</div>
