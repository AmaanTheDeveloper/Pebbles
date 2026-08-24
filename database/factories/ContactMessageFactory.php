<?php

namespace Database\Factories;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'status' => ContactMessageStatus::New,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
