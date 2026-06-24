<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|min:1',
        ]);

        $users = $this->userService->search($request->query('username'), $request->user());

        return $this->successResponse(
            UserResource::collection($users),
            'Pencarian pengguna berhasil.'
        );
    }

    public function show(string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();

        return $this->successResponse(
            new UserResource($user),
            'Profil pengguna berhasil dimuat.'
        );
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userService->updateProfile($request->user(), $request->validated());

        return $this->successResponse(
            new UserResource($user),
            'Profil berhasil diperbarui.'
        );
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $this->userService->updateAvatar($request->user(), $request->file('avatar'));

        return $this->successResponse(
            new UserResource($user),
            'Foto profil berhasil diperbarui.'
        );
    }

    public function block(Request $request, int $id): JsonResponse
    {
        $this->userService->block($request->user(), $id);

        return $this->successResponse(null, 'Pengguna berhasil diblokir.');
    }

    public function unblock(Request $request, int $id): JsonResponse
    {
        $this->userService->unblock($request->user(), $id);

        return $this->successResponse(null, 'Blokir pengguna berhasil dibuka.');
    }
}
