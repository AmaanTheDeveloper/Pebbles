<?php

namespace Database\Factories;

use App\Models\MenuBookPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuBookPage>
 */
class MenuBookPageFactory extends Factory
{
    protected $model = MenuBookPage::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'image_path' => 'menu-book/sample.jpg',
            'display_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
