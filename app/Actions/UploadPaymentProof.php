<?php

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Events\PaymentProofUploaded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadPaymentProof
{
    /**
     * @return array{payment: Payment, proof: PaymentProof}
     */
    public function handle(Order $order, User $user, UploadedFile $file, ?string $transactionReference = null): array
    {
        $payment = $order->payment;

        if ($payment === null) {
            throw ValidationException::withMessages([
                'payment' => 'No payment record exists for this order.',
            ]);
        }

        if ($order->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'payment' => 'You are not allowed to upload proof for this order.',
            ]);
        }

        if (! $payment->canUploadProof()) {
            throw ValidationException::withMessages([
                'proof' => $payment->requiresProof()
                    ? 'Payment proof cannot be uploaded in the current payment state.'
                    : 'This payment method does not require a proof upload.',
            ]);
        }

        if ($payment->status === PaymentStatus::Verified) {
            throw ValidationException::withMessages([
                'proof' => 'Verified payments cannot accept a replacement proof.',
            ]);
        }

        $disk = config('pebbles.payment_proof_disk', 'local');
        $directory = trim((string) config('pebbles.payment_proof_directory', 'payment-proofs'), '/');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $safeName = Str::uuid()->toString().'.'.$extension;
        $path = $directory.'/'.$order->public_order_number.'/'.$safeName;

        return DB::transaction(function () use ($order, $payment, $file, $disk, $path, $transactionReference): array {
            Storage::disk($disk)->putFileAs(
                dirname($path),
                $file,
                basename($path),
            );

            $proof = PaymentProof::query()->create([
                'payment_id' => $payment->id,
                'disk' => $disk,
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_at' => now(),
            ]);

            $payment->update([
                'status' => PaymentStatus::Submitted,
                'transaction_reference' => $transactionReference ?? $payment->transaction_reference,
                'rejection_reason' => null,
                'verified_at' => null,
                'verified_by' => null,
                'paid_at' => null,
            ]);

            $result = [
                'payment' => $payment->fresh('proofs'),
                'proof' => $proof,
            ];

            PaymentProofUploaded::dispatch($order->fresh(['branch', 'user']), $result['payment'], $proof);

            return $result;
        });
    }
}
