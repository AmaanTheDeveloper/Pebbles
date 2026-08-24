<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\PaymentMethodCatalog;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'method',
        'status',
        'amount',
        'transaction_reference',
        'paid_at',
        'verified_at',
        'verified_by',
        'rejection_reason',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function requiresProof(): bool
    {
        return app(PaymentMethodCatalog::class)->requiresProof($this->method);
    }

    public function canChangeMethod(): bool
    {
        return in_array($this->status, [
            PaymentStatus::Pending,
            PaymentStatus::Rejected,
            PaymentStatus::Failed,
        ], true);
    }

    public function canUploadProof(): bool
    {
        if (! $this->requiresProof()) {
            return false;
        }

        if ($this->order?->status === OrderStatus::Cancelled) {
            return false;
        }

        return in_array($this->status, [
            PaymentStatus::Pending,
            PaymentStatus::Submitted,
            PaymentStatus::Rejected,
        ], true);
    }

    public function canBeVerified(): bool
    {
        return $this->status === PaymentStatus::Submitted
            || ($this->method === PaymentMethod::Cod && $this->status === PaymentStatus::Pending);
    }

    public function canBeRejected(): bool
    {
        return $this->status === PaymentStatus::Submitted;
    }

    public function latestProof(): ?PaymentProof
    {
        if ($this->relationLoaded('proofs')) {
            return $this->proofs->sortByDesc('id')->first();
        }

        return $this->proofs()->latest('id')->first();
    }
}
