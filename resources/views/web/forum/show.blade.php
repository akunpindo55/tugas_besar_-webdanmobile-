@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="clay bg-white p-6 mb-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-bold bg-brand-lilac">
                    {{ strtoupper(substr($forum->name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold">{{ $forum->name }}</h1>
                    <p class="text-sm text-gray-500">{{ $forum->description ?? 'Tidak ada deskripsi' }}</p>
                    <div class="flex space-x-4 text-xs text-gray-400 mt-1">
                        <span>{{ $forum->members->count() }} anggota</span>
                        <span>{{ $topics->count() }} topik</span>
                    </div>
                </div>
            </div>
            <div class="flex space-x-2">
                @if($isMember)
                    <a href="#new-topic" class="clay-btn px-4 py-2 bg-brand-peach font-bold text-sm">+ Topik</a>
                    <form method="POST" action="{{ route('forums.leave', $forum->id) }}" onsubmit="return confirm('Yakin ingin keluar?')">
                        @csrf
                        <button class="clay-sm px-3 py-2 bg-red-50 text-sm font-bold text-red-600">Keluar</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('forums.join', $forum->id) }}">
                        @csrf
                        <button class="clay-btn px-4 py-2 bg-brand-mint font-bold text-sm">Gabung</button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Members -->
        @if($isMember)
        <div class="mt-6 pt-4 border-t-2 border-gray-100">
            <h3 class="font-bold text-sm text-gray-600 mb-2">Anggota ({{ $forum->members->count() }})</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($forum->members as $member)
                <span class="clay-sm px-3 py-1 text-xs font-medium bg-gray-50 flex items-center space-x-1">
                    <span>{{ $member->name }}</span>
                    @if($member->pivot->role === 'owner')
                    <span class="text-brand-peach">👑</span>
                    @elseif($member->pivot->role === 'admin')
                    <span class="text-xs text-gray-400">(admin)</span>
                    @endif
                </span>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Topics -->
    <div class="space-y-4">
        @forelse($topics as $topic)
        <a href="{{ route('topics.show', $topic->id) }}" class="block clay bg-white p-5 hover:shadow-lg transition">
            <div class="flex items-start space-x-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold bg-brand-blue flex-shrink-0">
                    {{ strtoupper(substr($topic->user->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-gray-900">{{ $topic->title }}</h3>
                    <p class="text-sm text-gray-500 truncate">{{ $topic->content }}</p>
                    <div class="text-xs text-gray-400 mt-1">
                        Oleh <strong>{{ $topic->user->name }}</strong> &bull; {{ $topic->created_at->diffForHumans() }}
                    </div>
                </div>
            </div>
        </a>
        @empty
        <div class="text-center py-12 clay bg-white">
            <p class="text-gray-500">Belum ada topik diskusi.</p>
        </div>
        @endforelse
    </div>

    <!-- New Topic Form (only for members) -->
    @if($isMember)
    <div id="new-topic" class="clay bg-white p-6 mt-6">
        <h3 class="font-bold text-lg mb-4">Buat Topik Baru</h3>
        <form method="POST" action="{{ route('forums.topics', $forum->id) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Judul</label>
                <input type="text" name="title" required class="w-full clay-input px-4 py-2.5 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Konten</label>
                <textarea name="content" rows="4" required class="w-full clay-input px-4 py-2.5 text-sm resize-none"></textarea>
            </div>
            <button type="submit" class="clay-btn bg-brand-peach font-bold py-2 px-6 text-sm">Buat Topik</button>
        </form>
    </div>
    @endif
</div>
@endsection
