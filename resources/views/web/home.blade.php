@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto" x-data="{
    posts: {{ Js::from($posts) }}
}">
    
    <!-- Create Post Form -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8" x-data="{
        content: '',
        visibility: 'public',
        isSubmitting: false,
        submitPost() {
            if(!this.content) return;
            this.isSubmitting = true;
            // Kita bisa pakai API endpoint nanti untuk submit via ajax
            // Untuk saat ini kita cukup console.log
            console.log('Submitting', this.content, this.visibility);
            setTimeout(() => {
                this.content = '';
                this.isSubmitting = false;
                alert('Post berhasil dibuat! (Nanti akan disambungkan ke server)');
            }, 1000);
        }
    }">
        <h3 class="text-lg font-bold mb-4">Buat Postingan</h3>
        <textarea x-model="content" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-peach focus:border-brand-peach outline-none resize-none mb-4" rows="3" placeholder="Apa kejadian seru di kampus hari ini?"></textarea>
        
        <div class="flex justify-between items-center">
            <div class="flex space-x-2">
                <button type="button" @click="visibility = 'public'" :class="visibility === 'public' ? 'bg-brand-mint bg-opacity-40 border-brand-mint' : 'bg-white border-gray-200'" class="px-3 py-1 text-sm rounded-full border transition font-medium">Publik</button>
                <button type="button" @click="visibility = 'private'" :class="visibility === 'private' ? 'bg-brand-lilac bg-opacity-40 border-brand-lilac' : 'bg-white border-gray-200'" class="px-3 py-1 text-sm rounded-full border transition font-medium">Internal</button>
            </div>
            
            <button @click="submitPost()" :disabled="isSubmitting || !content" class="bg-brand-peach text-gray-900 font-bold py-2 px-6 rounded-xl hover:opacity-90 transition disabled:opacity-50">
                <span x-show="!isSubmitting">Kirim</span>
                <span x-show="isSubmitting">Loading...</span>
            </button>
        </div>
    </div>

    <!-- Feed -->
    <div class="space-y-6">
        @foreach($posts as $post)
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-brand-blue rounded-full flex items-center justify-center font-bold text-gray-700">
                        {{ strtoupper(substr($post->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-bold text-gray-900">{{ $post->user->name }}</div>
                        <div class="text-xs text-gray-500">{{ $post->created_at->diffForHumans() }} &bull; {{ ucfirst($post->visibility) }}</div>
                    </div>
                </div>
            </div>
            
            <p class="text-gray-800 whitespace-pre-wrap mb-4">{{ $post->content }}</p>
            
            <div class="flex items-center space-x-6 text-gray-500 text-sm border-t border-gray-100 pt-4" x-data="{ showComments: false }">
                <button class="flex items-center space-x-2 hover:text-brand-peach transition">
                    <span>❤️</span>
                    <span class="font-bold">{{ $post->likes_count ?? 0 }}</span>
                </button>
                <button @click="showComments = !showComments" class="flex items-center space-x-2 hover:text-brand-blue transition">
                    <span>💬</span>
                    <span class="font-bold">{{ $post->comments_count ?? 0 }}</span>
                </button>
            </div>
        </div>
        @endforeach
        
        @if(count($posts) == 0)
        <div class="text-center text-gray-500 py-10">
            Belum ada postingan. Jadilah yang pertama!
        </div>
        @endif
    </div>

</div>
@endsection
