<?php

use App\Enums\ContactMessageStatus;
use App\Models\Branch;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use App\Support\ContactSettings;

test('guest can submit a contact message', function () {
    $this->postJson('/api/v1/catalog/contact-messages', [
        'name' => 'Ali Raza',
        'email' => 'ali@example.com',
        'phone' => '03001234567',
        'subject' => 'Catering enquiry',
        'message' => 'Do you offer catering for birthday parties at Pebbles?',
    ])
        ->assertCreated()
        ->assertJsonFragment([
            'message' => 'Thanks! Your message has been sent successfully.',
        ]);

    $this->assertDatabaseHas('contact_messages', [
        'email' => 'ali@example.com',
        'subject' => 'Catering enquiry',
        'status' => ContactMessageStatus::New->value,
    ]);
});

test('contact endpoint exposes one branch on contact and hides locations page', function () {
    Setting::setValue('contact_phone', '+92 300 1234567', 'string');
    Setting::setValue('contact_email', 'hello@pebbles.test', 'string');

    $branch = Branch::factory()->create([
        'name' => 'Pebbles DHA',
        'is_active' => true,
        'display_order' => 1,
    ]);

    $this->getJson('/api/v1/catalog/contact')
        ->assertSuccessful()
        ->assertJsonPath('data.phone', '+92 300 1234567')
        ->assertJsonPath('data.email', 'hello@pebbles.test')
        ->assertJsonPath('data.active_location_count', 1)
        ->assertJsonPath('data.show_locations_page', false)
        ->assertJsonPath('data.primary_location.name', 'Pebbles DHA')
        ->assertJsonPath('data.locations', []);
});

test('contact endpoint enables locations page when two active branches exist', function () {
    Branch::factory()->create(['name' => 'Pebbles DHA', 'is_active' => true, 'display_order' => 1]);
    Branch::factory()->create(['name' => 'Pebbles Gulberg', 'is_active' => true, 'display_order' => 2]);
    Branch::factory()->create(['name' => 'Closed Branch', 'is_active' => false]);

    $this->getJson('/api/v1/catalog/contact')
        ->assertSuccessful()
        ->assertJsonPath('data.active_location_count', 2)
        ->assertJsonPath('data.show_locations_page', true)
        ->assertJsonPath('data.primary_location', null)
        ->assertJsonCount(2, 'data.locations');
});

test('superadmin can manage contact messages', function () {
    $admin = User::factory()->superAdmin()->create();
    $message = ContactMessage::factory()->create([
        'status' => ContactMessageStatus::New,
    ]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/contact-messages/'.$message->id, [
            'status' => ContactMessageStatus::Read->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', ContactMessageStatus::Read->value);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/contact-messages')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $message->id);
});

test('superadmin can update contact settings', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings', [
            'customer_order_cancellation_enabled' => true,
            'phone' => '+92 300 9999999',
            'email' => 'contact@pebbles.test',
            'timezone' => 'Asia/Karachi',
            'opening_hours' => [
                'monday' => ['closed' => false, 'opens' => '12:00', 'closes' => '23:00', 'note' => null],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.phone', '+92 300 9999999')
        ->assertJsonPath('data.customer_order_cancellation_enabled', true);

    expect(ContactSettings::publicPayload()['phone'])->toBe('+92 300 9999999');
});
