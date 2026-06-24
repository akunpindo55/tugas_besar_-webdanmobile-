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

        <hr class="my-8 border-2 border-gray-100">

        <!-- Logout -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full clay-btn bg-red-50 text-red-600 font-bold py-2.5 text-sm hover:bg-red-100 transition">Keluar</button>
        </form>
    </div>
</div>
@endsection
