<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Manager = 'manager';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Manager => 'Manager',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function canAccessAdmin(): bool
    {
        return $this === self::Manager || $this === self::SuperAdmin;
    }

    public function requiresBranch(): bool
    {
        return $this === self::Manager;
    }

    /**
     * @return list<self>
     */
    public static function adminRoles(): array
    {
        return [self::Manager, self::SuperAdmin];
    }
}
