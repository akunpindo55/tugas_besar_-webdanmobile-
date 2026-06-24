@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-10">
    <div class="clay bg-white p-8">
        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center text-2xl clay-sm" style="background: linear-gradient(135deg, #A8E6CF, #BDE0FE);">📝</div>
            <h2 class="text-2xl font-bold">Daftar Baru</h2>
        </div>
        
        @if ($errors->any())
            <div class="bg-red-50 text-red-600 p-4 rounded-2xl mb-6 text-sm">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Kata Sandi</label>
                <input type="password" name="password" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-1">Konfirmasi Kata Sandi</label>
                <input type="password" name="password_confirmation" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <button type="submit" class="w-full clay-btn" style="background: linear-gradient(135deg, #A8E6CF, #BDE0FE); font-weight: 700; font-size: 0.875rem; padding: 0.625rem 1rem;">Daftar</button>
        </form>
        
        <p class="mt-6 text-center text-sm text-gray-600">
            Sudah punya akun? <a href="{{ route('login') }}" class="font-bold" style="color: #A8E6CF;">Masuk di sini</a>
        </p>
    </div>
</div>
@endsection
