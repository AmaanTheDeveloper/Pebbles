<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('superadmin users can access admin foundation routes', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->getJson('/api/admin/ping')
        ->assertSuccessful()
        ->assertJsonPath('message', 'admin');

    expect($user->isSuperAdmin())->toBeTrue()
        ->and(Gate::forUser($user)->allows('access-admin'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('role', UserRole::SuperAdmin))->toBeTrue();
});

test('regular users cannot access admin foundation routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/admin/ping')
        ->assertForbidden();

    expect($user->isUser())->toBeTrue()
        ->and(Gate::forUser($user)->denies('access-admin'))->toBeTrue();
});
