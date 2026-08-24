<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\ProfileSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_photo' => ProfileSettings::photoPayload($this->resource),
            'role' => $this->role?->value,
            'role_label' => $this->role?->label(),
            'access_label' => $this->accessLabel(),
            'branch_id' => $this->branch_id,
            'branch' => $this->when(
                $this->relationLoaded('branch') && $this->branch !== null,
                fn () => [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'code' => $this->branch->code,
                    'slug' => $this->branch->slug,
                ],
            ),
            'default_address' => $this->whenLoaded(
                'defaultAddress',
                fn () => $this->defaultAddress
                    ? (new AddressResource($this->defaultAddress))->resolve()
                    : null,
            ),
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function accessLabel(): string
    {
        if ($this->isSuperAdmin()) {
            return 'All Branches';
        }

        if ($this->isManager()) {
            return $this->branch?->name ?? 'Assigned Branch';
        }

        return 'Customer';
    }
}
