@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto">
    <div class="clay bg-white p-8">
        <h2 class="text-2xl font-bold text-center mb-6">Buat Forum Baru</h2>

        @if(session('error'))
        <div class="bg-red-50 text-red-600 p-4 rounded-2xl mb-4 text-sm">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('forums.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Forum</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full clay-input px-4 py-2.5 text-sm resize-none">{{ old('description') }}</textarea>
            </div>
            <div class="mb-6">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="is_private" value="1" class="w-5 h-5 rounded clay-sm">
                    <span class="text-sm font-medium text-gray-700">Forum Privat (hanya dengan undangan)</span>
                </label>
            </div>
            <button type="submit" class="w-full clay-btn bg-brand-peach font-bold py-2.5 text-sm">Buat Forum</button>
        </form>
    </div>
</div>
@endsection
