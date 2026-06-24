<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function show(Request $request, $username)
    {
        $user = User::withCount(['posts'])->where('username', $username)->firstOrFail();

        $posts = $user->posts()
            ->with(['media', 'reactions'])
            ->where(function ($q) use ($request) {
                $q->where('visibility', 'public');
                if ($request->user()->id === $user->id) {
                    $q->orWhere('visibility', 'private');
                }
            })
            ->latest()
            ->paginate(10);

        $isBlocked = $request->user()->blockedUsers()->where('blocked_user_id', $user->id)->exists();
        $isBlockedBy = $request->user()->blockedByUsers()->where('user_id', $user->id)->exists();

        return view('web.profile.show', compact('user', 'posts', 'isBlocked', 'isBlockedBy'));
    }

    public function edit(Request $request)
    {
        $user = $request->user();
        return view('web.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'bio' => 'nullable|string|max:500',
        ]);

        try {
            $this->userService->updateProfile($request->user(), $validated);
            return back()->with('success', 'Profil berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function avatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $this->userService->updateAvatar($request->user(), $request->file('avatar'));
            return back()->with('success', 'Foto profil berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('success', 'Kata sandi berhasil diubah.');
    }

    public function block(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak dapat memblokir diri sendiri.');
        }

        try {
            $this->userService->block($request->user(), $user->id);
            return back()->with('success', 'Pengguna berhasil diblokir.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function unblock(Request $request, User $user)
    {
        try {
            $this->userService->unblock($request->user(), $user->id);
            return back()->with('success', 'Blokir berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
