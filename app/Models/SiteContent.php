<?php

namespace App\Models;

use Database\Factories\SiteContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    /** @use HasFactory<SiteContentFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }
}
