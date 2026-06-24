<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function edit(Request $request)
    {
        $user = $request->user();
        return view('web.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
        ]);

        try {
            $this->userService->updateProfile($request->user(), $validated);
            return back()->with('success', 'Profil berhasil diperbarui.');
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
}
