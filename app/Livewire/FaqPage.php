<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\FaqTopic;
use App\Livewire\Concerns\SetsPageSeo;
use App\Models\Faq;
use App\Seo\FaqStructuredData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class FaqPage extends Component
{
    use SetsPageSeo;

    public const string ALL_TOPICS = 'Semua';

    /**
     * How to use the site, not editorial data — deliberately kept out of the schema.
     *
     * @var list<array{n: string, title: string, text: string}>
     */
    private const array STEPS = [
        ['n' => '1', 'title' => 'Pilih dua HP', 'text' => 'Ketik nama HP di dua kolom pencarian, atau pakai pintasan duel populer di bawah tombol bandingkan.'],
        ['n' => '2', 'title' => 'Baca penanda unggul', 'text' => 'Setiap baris menandai HP yang menang. Nyalakan “hanya perbedaan” kalau ingin melihat yang berbeda saja.'],
        ['n' => '3', 'title' => 'Cek harga sebelum beli', 'text' => 'Buka daftar penjual untuk melihat harga per toko hari ini, lalu pasang notifikasi kalau ingin menunggu harga turun.'],
    ];

    #[Url(as: 'topik')]
    public string $topic = self::ALL_TOPICS;

    /** Index of the open accordion item within the filtered list; -1 when all are closed. */
    public int $open = 0;

    /** @return Collection<int, Faq> */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::query()
            ->when($this->topic !== self::ALL_TOPICS, fn ($query) => $query->where('topic', $this->topic))
            ->orderBy('sort')
            ->get();
    }

    public function selectTopic(string $topic): void
    {
        $this->topic = $topic;
        $this->open = 0;
    }

    public function toggle(int $index): void
    {
        $this->open = $this->open === $index ? -1 : $index;
    }

    public function render(): View
    {
        $this->seo('Pusat bantuan', 'Jawaban atas pertanyaan yang paling sering masuk soal data harga, cara penilaian, dan penggunaan Bandingin.id.');

        app(FaqStructuredData::class)->handle($this->faqs());

        return view('livewire.faq-page', [
            'steps' => self::STEPS,
            'topics' => [self::ALL_TOPICS, ...FaqTopic::values()],
        ]);
    }
}
