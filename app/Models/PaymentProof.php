<?php

namespace App\Models;

use Database\Factories\PaymentProofFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProof extends Model
{
    /** @use HasFactory<PaymentProofFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size',
        'uploaded_at',
    ];

    protected $attributes = [
        'disk' => 'local',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentProof $proof): void {
            if ($proof->uploaded_at === null) {
                $proof->uploaded_at = now();
            }

            if (blank($proof->disk)) {
                $proof->disk = config('pebbles.payment_proof_disk', 'local');
            }
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Payment proofs must live on the private local disk, never the public disk.
     */
    public function isStoredPrivately(): bool
    {
        return $this->disk === config('pebbles.payment_proof_disk', 'local')
            && $this->disk !== 'public'
            && ! str_starts_with(ltrim($this->path, '/'), 'public/');
    }
}
