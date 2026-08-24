<?php

namespace Database\Factories;

use App\Enums\GalleryCategory;
use App\Models\GalleryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryItem>
 */
class GalleryItemFactory extends Factory
{
    protected $model = GalleryItem::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'image_path' => 'gallery/sample.jpg',
            'alt_text' => fake()->sentence(3),
            'gradient' => null,
            'category' => fake()->randomElement(GalleryCategory::cases()),
            'display_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function gradientPlaceholder(): static
    {
        return $this->state(fn (): array => [
            'image_path' => null,
            'gradient' => 'from-brand-green to-brand-green-light',
            'category' => GalleryCategory::Interior,
        ]);
    }
}
