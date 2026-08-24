<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Events\ContactMessageSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreContactMessageRequest;
use App\Http\Resources\Catalog\BranchResource;
use App\Models\Branch;
use App\Models\ContactMessage;
use App\Support\ContactSettings;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function show(): JsonResponse
    {
        $branches = Branch::query()->catalog()->get();
        $activeCount = $branches->count();

        return response()->json([
            'data' => array_merge(ContactSettings::publicPayload(), [
                'active_location_count' => $activeCount,
                'show_locations_page' => $activeCount >= 2,
                'primary_location' => $activeCount === 1
                    ? (new BranchResource($branches->first()))->resolve()
                    : null,
                'locations' => $activeCount >= 2
                    ? BranchResource::collection($branches)->resolve()
                    : [],
            ]),
        ]);
    }

    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $contactMessage = ContactMessage::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'subject' => $request->validated('subject'),
            'message' => $request->validated('message'),
            'ip_address' => $request->ip(),
        ]);

        ContactMessageSubmitted::dispatch($contactMessage);

        return response()->json([
            'message' => 'Thanks! Your message has been sent successfully.',
        ], 201);
    }
}
