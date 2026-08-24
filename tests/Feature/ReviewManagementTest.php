<?php

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;

test('guest can submit a review that starts pending', function () {
    $this->postJson('/api/v1/catalog/reviews', [
        'name' => 'Sarah Khan',
        'email' => 'sarah@example.com',
        'rating' => 5,
        'review' => 'Absolutely loved the chocolate shake. The ambience was amazing.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Sarah Khan')
        ->assertJsonFragment([
            'message' => 'Thank you! Your review has been submitted and is awaiting approval.',
        ]);

    $this->assertDatabaseHas('reviews', [
        'email' => 'sarah@example.com',
        'status' => ReviewStatus::Pending->value,
    ]);
});

test('pending reviews are hidden from the public catalog', function () {
    Review::factory()->create([
        'name' => 'Hidden Guest',
        'status' => ReviewStatus::Pending,
    ]);

    $this->getJson('/api/v1/catalog/reviews')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

test('approved reviews appear in the public catalog and summary', function () {
    Review::factory()->approved()->create([
        'name' => 'Visible Guest',
        'rating' => 5,
        'review' => 'Great shakes and friendly service every time.',
    ]);

    $this->getJson('/api/v1/catalog/reviews')
        ->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Visible Guest');

    $this->getJson('/api/v1/catalog/reviews/summary')
        ->assertSuccessful()
        ->assertJsonPath('data.total_reviews', 1)
        ->assertJsonPath('data.average_rating', 5);
});

test('duplicate review submissions from the same email within an hour are blocked', function () {
    Review::factory()->create([
        'email' => 'repeat@example.com',
        'created_at' => now()->subMinutes(10),
    ]);

    $this->postJson('/api/v1/catalog/reviews', [
        'name' => 'Repeat Guest',
        'email' => 'repeat@example.com',
        'rating' => 4,
        'review' => 'Trying to submit another review too soon.',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('superadmin can approve reject feature edit and delete reviews', function () {
    $admin = User::factory()->superAdmin()->create();
    $review = Review::factory()->create([
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/reviews/'.$review->id, [
            'status' => ReviewStatus::Approved->value,
            'is_featured' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', ReviewStatus::Approved->value)
        ->assertJsonPath('data.is_featured', true);

    $this->getJson('/api/v1/catalog/reviews?featured=1')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $review->id);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/reviews/'.$review->id, [
            'status' => ReviewStatus::Rejected->value,
        ])
        ->assertSuccessful();

    $this->getJson('/api/v1/catalog/reviews')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/reviews/'.$review->id)
        ->assertSuccessful();

    $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
});

test('non-admin cannot manage reviews', function () {
    $user = User::factory()->create();
    $review = Review::factory()->create();

    $this->actingAs($user)
        ->patchJson('/api/v1/admin/reviews/'.$review->id, [
            'status' => ReviewStatus::Approved->value,
        ])
        ->assertForbidden();
});
