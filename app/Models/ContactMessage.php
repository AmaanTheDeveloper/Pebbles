<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'ip_address',
    ];

    protected $attributes = [
        'status' => ContactMessageStatus::New->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
        ];
    }
}
