@props(['showWarranty' => false])

<div {{ $attributes->merge(['class' => 'max-w-[720px] text-[11.5px] leading-[1.55] text-muted']) }}>
    @if ($showWarranty)
        <div>Harga garansi resmi. Distributor biasanya lebih murah Rp200–500rb.</div>
        <div class="mt-1">Bandingin dapat komisi kecil dari pembelian lewat tautan ini — tanpa biaya tambahan buat kamu.</div>
    @else
        Harga garansi resmi, diperbarui hari ini. Bandingin dapat komisi kecil dari pembelian lewat tautan ini — tanpa biaya tambahan buat kamu.
    @endif
</div>
