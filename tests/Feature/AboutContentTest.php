<?php

use App\Models\SiteContent;
use App\Models\User;
use App\Support\AboutContent;

test('public about api returns defaults when no content is stored', function () {
    SiteContent::query()->where('key', AboutContent::SITE_CONTENT_KEY)->delete();

    $this->getJson('/api/v1/catalog/about')
        ->assertSuccessful()
        ->assertJsonPath('data.story.title', AboutContent::defaults()['story']['title'])
        ->assertJsonPath('data.values.items.0.title', 'Freshness')
        ->assertJsonPath('data.team.roles.0.role', 'Founder');
});

test('superadmin can update about content via site_contents', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/about', [
            'story' => [
                'title' => 'Our handmade story',
                'paragraphs' => ['First custom paragraph.', 'Second custom paragraph.'],
            ],
            'values' => [
                'items' => [
                    ['icon' => 'X', 'title' => 'Extra Care', 'description' => 'Crafted with care.'],
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'About page content saved.')
        ->assertJsonPath('data.story.title', 'Our handmade story')
        ->assertJsonPath('data.values.items.0.title', 'Extra Care');

    expect(SiteContent::query()->where('key', AboutContent::SITE_CONTENT_KEY)->exists())->toBeTrue();

    $this->getJson('/api/v1/catalog/about')
        ->assertSuccessful()
        ->assertJsonPath('data.story.title', 'Our handmade story')
        ->assertJsonPath('data.story.paragraphs.0', 'First custom paragraph.')
        ->assertJsonPath('data.values.items.0.title', 'Extra Care')
        ->assertJsonPath('data.philosophy.title', AboutContent::defaults()['philosophy']['title']);
});

test('guest cannot update about content', function () {
    $this->patchJson('/api/v1/admin/about', [
        'story' => ['title' => 'Nope'],
    ])->assertUnauthorized();
});

test('manager cannot update about content', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->patchJson('/api/v1/admin/about', [
            'story' => ['title' => 'Nope'],
        ])
        ->assertForbidden();
});
