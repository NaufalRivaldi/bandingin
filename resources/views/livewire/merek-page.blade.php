<div class="bg-white">
    <x-site-nav active="merek" />

    <x-page-hero
        :title="$this->activeBrand->name . ' di Indonesia'"
        :lead="$this->activeBrand->blurb"
        :items="[['label' => 'Merek'], ['label' => $this->activeBrand->name]]"
        width="medium"
    >
        <div class="mt-[clamp(20px,2.6vw,30px)] flex flex-wrap gap-2">
            @foreach ($this->brandNames as $name)
                <x-chip
                    wire:key="brand-{{ $name }}"
                    wire:click="selectBrand('{{ $name }}')"
                    tone="dark"
                    :active="$name === $this->activeBrand->name"
                >{{ $name }}</x-chip>
            @endforeach
        </div>
    </x-page-hero>

    <section class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(26px,3.4vw,44px)]">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,160px),1fr))] gap-3.5">
            @foreach ($this->stats as $label => $value)
                <x-stat-card wire:key="stat-{{ $label }}" :label="$label" :value="$value" />
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(34px,4.4vw,62px)]">
        <x-section-heading eyebrow="Katalog" :title="'Semua HP ' . $this->activeBrand->name">
            <a href="{{ route('katalog') }}" class="text-[13.5px] font-semibold">Buka katalog lengkap →</a>
        </x-section-heading>

        <div class="mt-[clamp(18px,2.4vw,26px)] grid grid-cols-[repeat(auto-fill,minmax(min(100%,240px),1fr))] gap-4">
            @foreach ($this->activeBrand->highlightedPhones as $model)
                <div wire:key="model-{{ $loop->index }}" class="rounded-[20px] border border-line bg-white p-4">
                    <div class="grid aspect-4/3 place-items-center rounded-[15px] border border-line-soft bg-[linear-gradient(160deg,#F4F6FB,#E4EAF5)]">
                        <x-device-mock class="h-[74%] w-[30%]" inset="inset-[3px]" face-radius="rounded-md" />
                    </div>

                    <div class="mt-3.5 font-display text-[16.5px] leading-[1.3] font-extrabold tracking-[-.02em] text-ink">{{ $model->name }}</div>
                    <div class="mt-[7px] font-mono text-[14.5px] font-medium">{{ $model->formattedPrice() }}</div>
                    <div class="mt-2 text-[12.5px] leading-[1.5] text-muted">{{ $model->network }}</div>

                    <a href="{{ route('bandingkan') }}" class="mt-3 inline-block text-[12.5px] font-semibold">Bandingkan →</a>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(34px,4.4vw,62px)]">
        <div class="rounded-3xl border border-line bg-surface p-[clamp(22px,2.8vw,34px)]">
            <x-eyebrow>Ringkasan redaksi</x-eyebrow>

            <div class="mt-4.5">
                <x-pros-cons
                    :pros="$this->activeBrand->pros"
                    :cons="$this->activeBrand->cons"
                    pros-label="Yang menonjol"
                    cons-label="Yang perlu dipertimbangkan"
                    :boxed="false"
                />
            </div>
        </div>
    </section>

    <x-site-footer />
</div>
