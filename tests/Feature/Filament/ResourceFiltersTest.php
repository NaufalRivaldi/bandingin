<?php

declare(strict_types=1);

use App\Filament\Resources\PhoneListings\Pages\ListPhoneListings;
use App\Filament\Resources\Phones\Pages\ListPhones;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Models\Brand;
use App\Models\Phone;
use App\Models\PhoneListing;
use App\Models\Report;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('filters phones by price band', function (): void {
    $cheap = Phone::factory()->create(['price' => 2_500_000]);
    $expensive = Phone::factory()->create(['price' => 9_000_000]);

    Livewire::test(ListPhones::class)
        ->filterTable('price_band', 'p2')
        ->assertCanSeeTableRecords([$cheap])
        ->assertCanNotSeeTableRecords([$expensive]);
});

it('filters phone listings by the brand of their phone', function (): void {
    $samsung = Brand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);
    $xiaomi = Brand::factory()->create(['name' => 'Xiaomi', 'slug' => 'xiaomi']);

    $samsungListing = PhoneListing::factory()->for(Phone::factory()->for($samsung))->create();
    $xiaomiListing = PhoneListing::factory()->for(Phone::factory()->for($xiaomi))->create();

    Livewire::test(ListPhoneListings::class)
        ->filterTable('brand', [$samsung->id])
        ->assertCanSeeTableRecords([$samsungListing])
        ->assertCanNotSeeTableRecords([$xiaomiListing]);
});

it('filters reports down to the ones nobody has triaged', function (): void {
    $untriaged = Report::factory()->create();
    $processed = Report::factory()->processed()->create();

    Livewire::test(ListReports::class)
        ->filterTable('status_triaged', false)
        ->assertCanSeeTableRecords([$untriaged])
        ->assertCanNotSeeTableRecords([$processed]);
});
