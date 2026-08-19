<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => ReportType::Hp,
            'status' => null,
            'subject' => 'POCO F7 Pro 12/256 GB',
            'email' => fake()->safeEmail(),
            'store' => null,
            'claimed_price' => null,
            'product_url' => null,
            'note' => fake()->sentence(),
        ];
    }

    public function processed(): self
    {
        return $this->state(fn (): array => ['status' => ReportStatus::Diproses]);
    }

    public function priceReport(): self
    {
        return $this->state(fn (): array => [
            'type' => ReportType::Harga,
            'subject' => 'Redmi Note 14 Pro 5G',
            'store' => 'Shopee Mall',
            'claimed_price' => 'Rp3.799.000',
            'product_url' => 'https://example.test/produk',
        ]);
    }
}
