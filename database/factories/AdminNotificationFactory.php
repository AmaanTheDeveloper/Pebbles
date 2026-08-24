<?php

namespace Database\Factories;

use App\Enums\AdminNotificationType;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminNotification>
 */
class AdminNotificationFactory extends Factory
{
    protected $model = AdminNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(AdminNotificationType::cases());

        return [
            'recipient_user_id' => User::factory()->superAdmin(),
            'type' => $type,
            'category' => $type->category(),
            'title' => fake()->sentence(4),
            'message' => fake()->sentence(12),
            'icon' => $type->icon(),
            'priority' => $type->defaultPriority(),
            'branch_id' => null,
            'related_type' => null,
            'related_id' => null,
            'data' => null,
            'action_url' => '/admin/orders',
            'dedupe_key' => fake()->unique()->uuid(),
            'read_at' => null,
            'archived_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
            'read_at' => now(),
        ]);
    }

    public function ofType(AdminNotificationType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'category' => $type->category(),
            'icon' => $type->icon(),
            'priority' => $type->defaultPriority(),
        ]);
    }
}
