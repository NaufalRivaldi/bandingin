{{-- Shown inside the editor, not on the public site. --}}
@if ($left && $right)
    <p><strong>Tabel perbandingan:</strong> {{ $left->name }} vs {{ $right->name }}</p>
@else
    <p><strong>Tabel perbandingan:</strong> HP tidak ditemukan.</p>
@endif
