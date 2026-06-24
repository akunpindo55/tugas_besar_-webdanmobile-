@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Profile Header -->
    <div class="clay bg-white p-8 mb-6 text-center">
        <div class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center text-3xl font-bold bg-brand-peach clay-sm">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
        <p class="text-sm text-gray-500">@{{ $user->username }}</p>
        @if($user->bio)
        <p class="text-gray-600 mt-3 max-w-md mx-auto">{{ $user->bio }}</p>
        @endif
        <div class="flex justify-center items-center space-x-6 mt-4 text-sm text-gray-500">
            <span><strong class="text-gray-900">{{ $posts->total() }}</strong> postingan</span>
        </div>

        @if($user->id === Auth::id())
            <a href="{{ route('profile.edit') }}" class="clay-btn inline-block mt-4 px-6 py-2 bg-brand-blue font-bold text-sm">Edit Profil</a>
        @else
            <div class="mt-4 flex justify-center space-x-3">
                <a href="{{ route('chat.index') }}" class="clay-btn px-4 py-2 bg-brand-mint font-bold text-sm">Chat</a>
                @if($isBlocked)
                    <form method="POST" action="{{ route('profile.unblock', $user->id) }}">
                        @csrf
                        <button class="clay-sm px-4 py-2 bg-gray-100 text-sm font-bold text-gray-600">Buka Blokir</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('profile.block', $user->id) }}">
                        @csrf
                        <button class="clay-sm px-4 py-2 bg-red-50 text-sm font-bold text-red-600">Blokir</button>
                    </form>
                @endif
            </div>
            @if($isBlockedBy)
            <p class="text-sm text-gray-400 mt-3">Anda tidak dapat berinteraksi dengan pengguna ini.</p>
            @endif
        @endif
    </div>

    <!-- User Posts -->
    <div class="space-y-6">
        @forelse($posts as $post)
        <div class="clay bg-white p-6">
            <div class="flex justify-between items-start mb-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold bg-brand-lilac">
                        {{ strtoupper(substr($post->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-bold text-sm text-gray-900">{{ $post->user->name }}</div>
                        <div class="text-xs text-gray-500">{{ $post->created_at->diffForHumans() }} &bull; {{ $post->visibility === 'public' ? 'Publik' : 'Internal' }}</div>
                    </div>
                </div>
            </div>
            <p class="text-gray-800 whitespace-pre-wrap mb-3">{{ $post->content }}</p>
            <div class="flex items-center space-x-4 text-sm text-gray-500 border-t-2 border-gray-100 pt-3">
                <span>❤️ {{ $post->reactions->where('reaction_type', 'like')->count() }}</span>
                <span>💬 {{ $post->comments_count ?? 0 }}</span>
            </div>
        </div>
        @empty
        <div class="text-center py-12 clay bg-white">
            <p class="text-gray-500">Belum ada postingan.</p>
        </div>
        @endforelse

        {{ $posts->links() }}
    </div>
</div>
@endsection
