<?php

use App\Enums\OfferType;
use App\Models\Offer;

test('catalog lists only currently available offers', function () {
    Offer::factory()->create([
        'title' => 'Active Combo',
        'type' => OfferType::ComboOffers,
        'is_active' => true,
    ]);
    Offer::factory()->inactive()->create([
        'title' => 'Inactive Deal',
        'type' => OfferType::Discounts,
    ]);
    Offer::factory()->expired()->create([
        'title' => 'Expired Event',
        'type' => OfferType::SpecialEvents,
    ]);
    Offer::factory()->upcoming()->create([
        'title' => 'Upcoming Seasonal',
        'type' => OfferType::SeasonalDeals,
    ]);

    $this->getJson('/api/v1/catalog/offers')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Active Combo')
        ->assertJsonPath('data.0.type', OfferType::ComboOffers->value)
        ->assertJsonPath('data.0.type_label', 'Combo Offers');
});

test('catalog can return all four offer types', function () {
    foreach (OfferType::cases() as $type) {
        Offer::factory()->create([
            'title' => $type->label(),
            'type' => $type,
            'is_active' => true,
        ]);
    }

    $response = $this->getJson('/api/v1/catalog/offers')
        ->assertSuccessful()
        ->assertJsonCount(4, 'data');

    $types = collect($response->json('data'))->pluck('type')->sort()->values()->all();

    expect($types)->toBe(collect(OfferType::cases())->map->value->sort()->values()->all());
});
