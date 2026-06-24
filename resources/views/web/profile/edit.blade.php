@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto">
    <div class="clay bg-white p-8">
        <h2 class="text-2xl font-bold text-center mb-6">Edit Profil</h2>

        @if(session('success'))
        <div class="bg-brand-mint bg-opacity-30 text-gray-800 p-4 rounded-2xl mb-4 text-sm font-medium">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 text-red-600 p-4 rounded-2xl mb-4 text-sm">
            {{ session('error') }}
        </div>
        @endif

        <!-- Avatar -->
        <div class="text-center mb-6">
            <div class="w-20 h-20 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl font-bold bg-brand-peach clay-sm">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data">
                @csrf
                <label class="clay-sm px-4 py-1.5 bg-gray-50 text-sm font-bold cursor-pointer inline-block">
                    Ganti Foto
                    <input type="file" name="avatar" class="hidden" accept="image/*" onchange="this.form.submit()">
                </label>
            </form>
        </div>

        <!-- Edit Form -->
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Username</label>
                <input type="text" value="{{ $user->username }}" disabled class="w-full clay-input px-4 py-2.5 text-sm bg-gray-50 text-gray-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                <input type="email" value="{{ $user->email }}" disabled class="w-full clay-input px-4 py-2.5 text-sm bg-gray-50 text-gray-500">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-1">Bio</label>
                <textarea name="bio" rows="3" class="w-full clay-input px-4 py-2.5 text-sm resize-none">{{ old('bio', $user->bio) }}</textarea>
            </div>
            <button type="submit" class="w-full clay-btn bg-brand-peach font-bold py-2.5 text-sm">Simpan</button>
        </form>

        <hr class="my-8 border-2 border-gray-100">

        <!-- Change Password -->
        <h3 class="font-bold text-lg mb-4">Ubah Kata Sandi</h3>
        <form method="POST" action="{{ route('profile.change-password') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Kata Sandi Saat Ini</label>
                <input type="password" name="current_password" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Kata Sandi Baru</label>
                <input type="password" name="new_password" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-1">Konfirmasi Kata Sandi Baru</label>
                <input type="password" name="new_password_confirmation" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <button type="submit" class="w-full clay-btn bg-brand-blue font-bold py-2.5 text-sm">Ubah Kata Sandi</button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('profile.show', Auth::user()->username) }}" class="text-sm text-gray-500 hover:underline">← Lihat Profil</a>
        </div>
    </div>
</div>
@endsection
