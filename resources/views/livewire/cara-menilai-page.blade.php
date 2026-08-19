<div class="overflow-x-hidden bg-white">
    <x-site-nav />

    <x-page-hero
        width="narrow"
        title="Cara kami menilai"
        lead="Setiap HP mendapat lima skor kategori dan satu skor akhir. Bobotnya sama untuk semua merek, dan angkanya dihitung ulang setiap kali harga berubah."
        :items="[['label' => 'Cara kami menilai']]"
    />

    <section class="mx-auto max-w-[820px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(30px,4vw,56px)]">
        <h2 class="font-display text-[clamp(21px,2.5vw,29px)] font-extrabold tracking-[-.028em]">Bobot penilaian</h2>

        <div class="mt-[22px] flex flex-col gap-[18px]">
            @foreach ($weights as $weight)
                <x-score-bar
                    wire:key="weight-{{ $loop->index }}"
                    :label="$weight['label']"
                    :value="$weight['pct']"
                    :max="$maxWeight"
                    suffix="%"
                >
                    <div class="mt-2 text-[13.5px] leading-relaxed text-muted">{{ $weight['desc'] }}</div>
                </x-score-bar>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-[820px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(34px,4.4vw,62px)]">
        <h2 class="font-display text-[clamp(21px,2.5vw,29px)] font-extrabold tracking-[-.028em]">Prosesnya</h2>

        <div class="mt-[22px] flex flex-col gap-3.5">
            @foreach ($steps as $step)
                <div wire:key="step-{{ $step['n'] }}" class="flex gap-4 rounded-[18px] border border-line bg-surface p-5">
                    <span class="grid size-[30px] flex-none place-items-center rounded-[10px] bg-brand font-mono text-[13px] font-semibold text-white">{{ $step['n'] }}</span>
                    <span class="flex-1">
                        <span class="block font-display text-base font-extrabold tracking-[-.02em]">{{ $step['title'] }}</span>
                        <span class="mt-[7px] block text-sm leading-relaxed text-muted">{{ $step['text'] }}</span>
                    </span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-[820px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(34px,4.4vw,62px)]">
        <h2 class="font-display text-[clamp(21px,2.5vw,29px)] font-extrabold tracking-[-.028em]">Yang tidak memengaruhi skor</h2>

        <div class="mt-5 flex flex-col gap-3">
            @foreach ($exclusions as $exclusion)
                <div wire:key="exclusion-{{ $loop->index }}" class="flex gap-3 text-[14.5px] leading-relaxed">
                    <span class="flex-none font-mono text-lose">×</span>
                    <span>{{ $exclusion }}</span>
                </div>
            @endforeach
        </div>

        <p class="mt-[22px] text-[13.5px] leading-relaxed text-muted">
            Kalau kamu menemukan skor yang terasa keliru, kirim koreksinya lewat <a href="{{ route('lapor') }}" class="text-brand hover:text-brand-dark">halaman laporan</a>. Kami tinjau setiap masukan dalam dua hari kerja.
        </p>
    </section>

    <x-site-footer />
</div>
