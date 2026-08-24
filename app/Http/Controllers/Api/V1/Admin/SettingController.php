<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\BrandingSettings;
use App\Support\ContactSettings;
use App\Support\PaymentSettings;
use App\Support\PopupSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => array_merge(
                ContactSettings::adminPayload(),
                BrandingSettings::adminPayload(),
                ['popup' => PopupSettings::adminPayload()],
                ['payment' => PaymentSettings::adminPayload()],
            ),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'order_estimated_minutes' => ['sometimes', 'integer', 'min:5', 'max:240'],
            'customer_order_cancellation_enabled' => ['sometimes', 'boolean'],
            'customer_order_modification_enabled' => ['sometimes', 'boolean'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'notification_email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:64', Rule::in(timezone_identifiers_list())],
            'social_instagram' => ['nullable', 'url', 'max:500'],
            'social_facebook' => ['nullable', 'url', 'max:500'],
            'social_tiktok' => ['nullable', 'url', 'max:500'],
            'social_youtube' => ['nullable', 'url', 'max:500'],
            'opening_hours' => ['nullable', 'array'],
            'opening_hours.*.closed' => ['sometimes', 'boolean'],
            'opening_hours.*.opens' => ['nullable', 'date_format:H:i'],
            'opening_hours.*.closes' => ['nullable', 'date_format:H:i'],
            'opening_hours.*.note' => ['nullable', 'string', 'max:160'],
        ]);

        ContactSettings::update($validated);

        return response()->json([
            'data' => array_merge(
                ContactSettings::adminPayload(),
                BrandingSettings::adminPayload(),
                ['popup' => PopupSettings::adminPayload()],
                ['payment' => PaymentSettings::adminPayload()],
            ),
            'message' => 'Settings saved.',
        ]);
    }

    public function updatePayment(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'methods' => ['sometimes', 'array'],
            'methods.cod.enabled' => ['sometimes', 'boolean'],
            'methods.jazzcash.enabled' => ['sometimes', 'boolean'],
            'methods.bank_transfer.enabled' => ['sometimes', 'boolean'],
            'methods.easypaisa.enabled' => ['sometimes', 'boolean'],
            'jazzcash' => ['sometimes', 'array'],
            'jazzcash.business_name' => ['nullable', 'string', 'max:160'],
            'jazzcash.till_id' => ['nullable', 'string', 'max:30'],
            'jazzcash.instructions' => ['nullable', 'string', 'max:1000'],
            'bank_transfer' => ['sometimes', 'array'],
            'bank_transfer.bank_name' => ['nullable', 'string', 'max:160'],
            'bank_transfer.account_title' => ['nullable', 'string', 'max:160'],
            'bank_transfer.account_number' => ['nullable', 'string', 'max:64'],
            'bank_transfer.iban' => ['nullable', 'string', 'max:64'],
            'bank_transfer.branch_code' => ['nullable', 'string', 'max:64'],
            'bank_transfer.instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = PaymentSettings::update($validated);

        return response()->json([
            'data' => ['payment' => $payment],
            'message' => 'Payment settings saved.',
        ]);
    }

    public function updateJazzcashQrImage(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        PaymentSettings::storeJazzcashQrImage($request->file('image'));

        return response()->json([
            'data' => ['payment' => PaymentSettings::adminPayload()],
            'message' => 'JazzCash QR image updated.',
        ]);
    }

    public function destroyJazzcashQrImage(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        PaymentSettings::removeJazzcashQrImage();

        return response()->json([
            'data' => ['payment' => PaymentSettings::adminPayload()],
            'message' => 'JazzCash QR image removed.',
        ]);
    }

    public function updatePopup(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'type' => ['sometimes', 'string', Rule::in(['welcome_offer', 'new_product', 'special_promotion', 'announcement'])],
            'heading' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'badge_text' => ['nullable', 'string', 'max:120'],
            'button_text' => ['nullable', 'string', 'max:120'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'delay_ms' => ['sometimes', 'integer', 'min:0', 'max:5000'],
            'frequency' => ['sometimes', 'string', Rule::in(['session', 'day', 'visit'])],
            'offer_id' => ['nullable', 'integer', 'exists:offers,id'],
        ]);

        if (array_key_exists('button_url', $validated) && $validated['button_url'] !== null && $validated['button_url'] !== '') {
            $url = $validated['button_url'];
            if (! str_starts_with($url, '/') && filter_var($url, FILTER_VALIDATE_URL) === false) {
                return response()->json([
                    'message' => 'The button URL must be a valid path or URL.',
                    'errors' => ['button_url' => ['The button URL must be a valid path or URL.']],
                ], 422);
            }
        }

        $popup = PopupSettings::update($validated);

        return response()->json([
            'data' => ['popup' => $popup],
            'message' => 'Popup settings updated successfully.',
        ]);
    }

    public function updatePopupImage(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        PopupSettings::storeImage($request->file('image'));

        return response()->json([
            'data' => ['popup' => PopupSettings::adminPayload()],
            'message' => 'Popup settings updated successfully.',
        ]);
    }

    public function destroyPopupImage(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        PopupSettings::removeImage();

        return response()->json([
            'data' => ['popup' => PopupSettings::adminPayload()],
            'message' => 'Popup settings updated successfully.',
        ]);
    }

    public function updateLogo(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        BrandingSettings::storeLogo($request->file('logo'));

        return response()->json([
            'data' => BrandingSettings::adminPayload(),
            'message' => 'Logo updated successfully.',
        ]);
    }

    public function destroyLogo(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        BrandingSettings::removeLogo();

        return response()->json([
            'data' => BrandingSettings::adminPayload(),
            'message' => 'Logo removed successfully.',
        ]);
    }
}
