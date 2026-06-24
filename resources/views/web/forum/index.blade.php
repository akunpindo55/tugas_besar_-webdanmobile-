@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Forum</h1>
        <div class="flex space-x-2">
            <a href="{{ route('forums.my') }}" class="clay-sm px-4 py-2 bg-gray-50 text-sm font-bold">Forum Saya</a>
            <a href="{{ route('forums.create') }}" class="clay-btn px-4 py-2 bg-brand-peach font-bold text-sm">Buat Forum</a>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($forums as $forum)
        <a href="{{ route('forums.show', $forum->id) }}" class="block clay bg-white p-5 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg bg-brand-lilac">
                        {{ strtoupper(substr($forum->name, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">{{ $forum->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $forum->description ?? 'Tidak ada deskripsi' }}</p>
                        <div class="flex space-x-4 text-xs text-gray-400 mt-1">
                            <span>{{ $forum->members_count }} anggota</span>
                            <span>{{ $forum->topics_count }} topik</span>
                        </div>
                    </div>
                </div>
                <div class="text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </div>
        </a>
        @empty
        <div class="text-center py-12 clay bg-white">
            <p class="text-gray-500">Belum ada forum publik.</p>
        </div>
        @endforelse

        {{ $forums->links() }}
    </div>
</div>
@endsection
