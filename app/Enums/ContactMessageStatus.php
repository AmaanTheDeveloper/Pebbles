<?php

namespace App\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Read => 'Read',
            self::Replied => 'Replied',
            self::Archived => 'Archived',
        };
    }
}
