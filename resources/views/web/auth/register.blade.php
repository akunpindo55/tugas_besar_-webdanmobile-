@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-10">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-center mb-6">Daftar Baru</h2>
        
        @if ($errors->any())
            <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 text-sm">
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-mint outline-none transition">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-mint outline-none transition">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-mint outline-none transition">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-mint outline-none transition">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Kata Sandi</label>
                <input type="password" name="password_confirmation" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-mint outline-none transition">
            </div>
            <button type="submit" class="w-full bg-brand-mint text-gray-900 font-bold py-3 px-4 rounded-xl hover:opacity-90 transition">
                Daftar
            </button>
        </form>
        
        <p class="mt-6 text-center text-sm text-gray-600">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-brand-mint font-bold hover:underline">Masuk di sini</a>
        </p>
    </div>
</div>
@endsection
