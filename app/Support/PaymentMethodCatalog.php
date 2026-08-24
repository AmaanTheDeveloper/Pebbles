<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use InvalidArgumentException;

class PaymentMethodCatalog
{
    /**
     * @return list<array{id: string, name: string, label: string, instructions: string, enabled: bool, requires_proof: bool, details: array<string, string>, qr_image: array{url: string, path: string}|null}>
     */
    public function enabled(): array
    {
        return collect(config('pebbles.payment_methods', []))
            ->filter(fn (array $method, string $id): bool => PaymentSettings::isMethodEnabled($id))
            ->map(fn (array $method, string $id): array => $this->format($id, $method))
            ->values()
            ->all();
    }

    /**
     * @return array{id: string, name: string, label: string, instructions: string, enabled: bool, requires_proof: bool, details: array<string, string>, qr_image: array{url: string, path: string}|null}
     */
    public function get(PaymentMethod|string $method): array
    {
        $id = $method instanceof PaymentMethod ? $method->value : $method;
        $config = config("pebbles.payment_methods.{$id}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Unknown payment method [{$id}].");
        }

        return $this->format($id, $config);
    }

    public function requiresProof(PaymentMethod|string $method): bool
    {
        return (bool) ($this->get($method)['requires_proof'] ?? false);
    }

    public function isEnabled(PaymentMethod|string $method): bool
    {
        $id = $method instanceof PaymentMethod ? $method->value : $method;

        return PaymentSettings::isMethodEnabled($id);
    }

    /**
     * @param  array<string, mixed>  $method
     * @return array{id: string, name: string, label: string, instructions: string, enabled: bool, requires_proof: bool, details: array<string, string>, qr_image: array{url: string, path: string}|null}
     */
    private function format(string $id, array $method): array
    {
        return [
            'id' => $id,
            'name' => $id,
            'label' => (string) ($method['label'] ?? $id),
            'instructions' => PaymentSettings::instructions($id),
            'enabled' => PaymentSettings::isMethodEnabled($id),
            'requires_proof' => (bool) ($method['requires_proof'] ?? false),
            'details' => PaymentSettings::customerDetails($id),
            'qr_image' => $id === 'jazzcash' ? PaymentSettings::jazzcashQrImagePayload() : null,
        ];
    }
}
