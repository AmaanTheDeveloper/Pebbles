<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_photo_path',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => UserRole::User->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function isUser(): bool
    {
        return $this->role === UserRole::User;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function canAccessAdmin(): bool
    {
        return $this->role?->canAccessAdmin() ?? false;
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(UserRole ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function belongsToBranch(?int $branchId): bool
    {
        if ($branchId === null) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isManager() && (int) $this->branch_id === (int) $branchId;
    }

    /**
     * @return list<int>
     */
    public function managedBranchIds(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }

        if ($this->isManager() && $this->branch_id !== null) {
            return [(int) $this->branch_id];
        }

        return [];
    }

    public function assignedBranchId(): ?int
    {
        return $this->isManager() ? ($this->branch_id !== null ? (int) $this->branch_id : null) : null;
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
