@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-10">
    <div class="clay bg-white p-8">
        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center text-2xl clay-sm" style="background: linear-gradient(135deg, #BDE0FE, #DCD3FF);">🔑</div>
            <h2 class="text-2xl font-bold">Masuk ke Kampus</h2>
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

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Email / Username</label>
                <input type="text" name="email" value="{{ old('email') }}" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-1">Kata Sandi</label>
                <input type="password" name="password" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <button type="submit" class="w-full clay-btn bg-brand-blue font-bold py-2.5 text-sm">Masuk</button>
        </form>
        
        <p class="mt-6 text-center text-sm text-gray-600">
            Belum punya akun? <a href="{{ route('register') }}" class="font-bold" style="color: #BDE0FE;">Daftar sekarang</a>
        </p>
    </div>
</div>
@endsection
