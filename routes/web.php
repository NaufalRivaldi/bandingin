<?php

declare(strict_types=1);

use App\Livewire\BeritaDetailPage;
use App\Livewire\BeritaPage;
use App\Livewire\CaraMenilaiPage;
use App\Livewire\FaqPage;
use App\Livewire\HomePage;
use App\Livewire\KatalogPage;
use App\Livewire\LaporPage;
use App\Livewire\LegalPage;
use App\Livewire\MerekPage;
use App\Livewire\PerbandinganPage;
use App\Livewire\PhoneDetailPage;
use App\Livewire\SumberHargaPage;
use Illuminate\Support\Facades\Route;

Route::livewire('/', HomePage::class)->name('home');
Route::livewire('/katalog', KatalogPage::class)->name('katalog');
Route::livewire('/bandingkan', PerbandinganPage::class)->name('bandingkan');
Route::livewire('/hp/{phone}', PhoneDetailPage::class)->name('hp.show');
Route::livewire('/merek', MerekPage::class)->name('merek');
Route::livewire('/berita', BeritaPage::class)->name('berita');
Route::livewire('/berita/{slug}', BeritaDetailPage::class)->name('berita.show');
Route::livewire('/faq', FaqPage::class)->name('faq');
Route::livewire('/lapor', LaporPage::class)->name('lapor');
Route::livewire('/legal', LegalPage::class)->name('legal');
Route::livewire('/cara-kami-menilai', CaraMenilaiPage::class)->name('cara-menilai');
Route::livewire('/sumber-data-harga', SumberHargaPage::class)->name('sumber-harga');
