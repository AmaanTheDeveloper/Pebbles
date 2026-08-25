<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\AboutContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        return response()->json([
            'data' => AboutContent::adminPayload(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'hero' => ['sometimes', 'array'],
            'hero.eyebrow' => ['nullable', 'string', 'max:120'],
            'hero.title_line1' => ['nullable', 'string', 'max:160'],
            'hero.title_accent' => ['nullable', 'string', 'max:160'],
            'hero.subtitle' => ['nullable', 'string', 'max:1000'],
            'story' => ['sometimes', 'array'],
            'story.eyebrow' => ['nullable', 'string', 'max:120'],
            'story.title' => ['nullable', 'string', 'max:160'],
            'story.title_accent' => ['nullable', 'string', 'max:160'],
            'story.paragraphs' => ['nullable', 'array', 'max:8'],
            'story.paragraphs.*' => ['nullable', 'string', 'max:1000'],
            'philosophy' => ['sometimes', 'array'],
            'philosophy.eyebrow' => ['nullable', 'string', 'max:120'],
            'philosophy.title' => ['nullable', 'string', 'max:160'],
            'philosophy.subtitle' => ['nullable', 'string', 'max:500'],
            'philosophy.items' => ['nullable', 'array', 'max:8'],
            'philosophy.items.*.icon' => ['nullable', 'string', 'max:8'],
            'philosophy.items.*.title' => ['nullable', 'string', 'max:120'],
            'philosophy.items.*.description' => ['nullable', 'string', 'max:500'],
            'team' => ['sometimes', 'array'],
            'team.eyebrow' => ['nullable', 'string', 'max:120'],
            'team.title' => ['nullable', 'string', 'max:160'],
            'team.subtitle' => ['nullable', 'string', 'max:500'],
            'team.roles' => ['nullable', 'array', 'max:8'],
            'team.roles.*.key' => ['nullable', 'string', 'max:64'],
            'team.roles.*.role' => ['nullable', 'string', 'max:120'],
            'team.roles.*.description' => ['nullable', 'string', 'max:500'],
            'team.roles.*.accent' => ['nullable', 'string', 'max:160'],
            'values' => ['sometimes', 'array'],
            'values.eyebrow' => ['nullable', 'string', 'max:120'],
            'values.title' => ['nullable', 'string', 'max:160'],
            'values.subtitle' => ['nullable', 'string', 'max:500'],
            'values.items' => ['nullable', 'array', 'max:12'],
            'values.items.*.icon' => ['nullable', 'string', 'max:8'],
            'values.items.*.title' => ['nullable', 'string', 'max:120'],
            'values.items.*.description' => ['nullable', 'string', 'max:500'],
            'ambience' => ['sometimes', 'array'],
            'ambience.eyebrow' => ['nullable', 'string', 'max:120'],
            'ambience.title' => ['nullable', 'string', 'max:160'],
            'ambience.subtitle' => ['nullable', 'string', 'max:500'],
            'ambience.points' => ['nullable', 'array', 'max:8'],
            'ambience.points.*.title' => ['nullable', 'string', 'max:120'],
            'ambience.points.*.text' => ['nullable', 'string', 'max:500'],
            'brand_statement' => ['sometimes', 'array'],
            'brand_statement.eyebrow' => ['nullable', 'string', 'max:120'],
            'brand_statement.title' => ['nullable', 'string', 'max:160'],
            'brand_statement.title_line2' => ['nullable', 'string', 'max:160'],
            'brand_statement.subtitle' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = AboutContent::update($validated);

        return response()->json([
            'data' => $payload,
            'message' => 'About page content saved.',
        ]);
    }
}
