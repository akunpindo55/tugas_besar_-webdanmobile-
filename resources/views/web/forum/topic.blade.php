@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('forums.show', $topic->forum_id) }}" class="text-sm text-gray-500 hover:underline">← Kembali ke forum</a>
    </div>

    <!-- Topic Header -->
    <div class="clay bg-white p-6 mb-6">
        <div class="flex items-start space-x-3 mb-4">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold bg-brand-peach flex-shrink-0">
                {{ strtoupper(substr($topic->user->name, 0, 1)) }}
            </div>
            <div class="flex-1">
                <h1 class="text-xl font-bold text-gray-900">{{ $topic->title }}</h1>
                <div class="text-xs text-gray-500">
                    Oleh <strong>{{ $topic->user->name }}</strong> &bull; {{ $topic->created_at->diffForHumans() }}
                </div>
            </div>
        </div>
        <div class="text-gray-700 whitespace-pre-wrap">{{ $topic->content }}</div>
    </div>

    <!-- Comments -->
    <div class="space-y-4">
        @forelse($topic->comments as $comment)
        <div class="clay-sm bg-white p-4">
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs bg-brand-lilac flex-shrink-0">
                    {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-sm">{{ $comment->user->name }}</span>
                        <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-gray-700 mt-1">{{ $comment->content }}</p>

                    <!-- Replies -->
                    @if($comment->replies->count() > 0)
                    <div class="mt-3 ml-4 space-y-2 border-l-2 border-gray-100 pl-4">
                        @foreach($comment->replies as $reply)
                        <div class="flex items-start space-x-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] bg-brand-mint flex-shrink-0">
                                {{ strtoupper(substr($reply->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-xs">{{ $reply->user->name }}</span>
                                    <span class="text-[10px] text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-xs text-gray-700">{{ $reply->content }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-8 clay-sm bg-white">
            <p class="text-gray-400 text-sm">Belum ada komentar.</p>
        </div>
        @endforelse
    </div>

    <!-- Reply Form -->
    @if($isMember)
    <div class="clay bg-white p-6 mt-6">
        <h3 class="font-bold text-lg mb-4">Tulis Komentar</h3>
        <form method="POST" action="{{ route('topics.comments', $topic->id) }}">
            @csrf
            <textarea name="content" rows="3" required class="w-full clay-input px-4 py-2.5 text-sm resize-none mb-4" placeholder="Tulis komentar..."></textarea>
            <button type="submit" class="clay-btn bg-brand-blue font-bold py-2 px-6 text-sm">Kirim Komentar</button>
        </form>
    </div>
    @endif
</div>
@endsection
