<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manage-users');

        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'role' => ['sometimes', 'nullable', Rule::enum(UserRole::class)],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $users = User::query()
            ->with('branch')
            ->when(
                filled($validated['search'] ?? null),
                function ($query) use ($validated): void {
                    $search = $validated['search'];
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
                }
            )
            ->when(
                filled($validated['role'] ?? null),
                fn ($query) => $query->where('role', $validated['role'])
            )
            ->when(
                filled($validated['branch_id'] ?? null),
                fn ($query) => $query->where('branch_id', $validated['branch_id'])
            )
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => UserResource::collection($users)->resolve(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ],
            'stats' => [
                'users' => User::query()->where('role', UserRole::User)->count(),
                'managers' => User::query()->where('role', UserRole::Manager)->count(),
                'super_admins' => User::query()->where('role', UserRole::SuperAdmin)->count(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('manage-users');

        return response()->json([
            'data' => (new UserResource($user->loadMissing('branch')))->resolve(),
        ]);
    }

    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('manage-users');

        $validated = $request->validated();
        $role = UserRole::from($validated['role']);

        if (
            $user->isSuperAdmin()
            && $role !== UserRole::SuperAdmin
            && User::query()->where('role', UserRole::SuperAdmin)->whereKeyNot($user->id)->doesntExist()
        ) {
            return response()->json([
                'message' => 'At least one Super Admin must remain active.',
                'errors' => [
                    'role' => ['At least one Super Admin must remain active.'],
                ],
            ], 422);
        }

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $role,
            'branch_id' => $role === UserRole::Manager ? $validated['branch_id'] : null,
        ])->save();

        return response()->json([
            'data' => (new UserResource($user->fresh('branch')))->resolve(),
            'message' => 'User updated successfully.',
        ]);
    }
}
