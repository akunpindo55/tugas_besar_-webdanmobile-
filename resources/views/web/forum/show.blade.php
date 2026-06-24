@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto" x-data="forumPage()">
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
                    <button @click="showTopicForm = !showTopicForm" class="clay-btn px-4 py-2 bg-brand-peach font-bold text-sm">+ Topik</button>
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
        <div class="clay bg-white p-5 hover:shadow-lg transition flex items-center group">
            <a href="{{ route('topics.show', $topic->id) }}" class="flex-1 flex items-start space-x-3 min-w-0">
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
            </a>
            @if($isMember && ($topic->user_id === Auth::id() || $userRole === 'owner' || $userRole === 'admin'))
            <button onclick="if(confirm('Hapus topik ini?')){ fetch('/forums/{{ $forum->id }}/topics/{{ $topic->id }}', {method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest','Accept':'application/json'}}).then(r=>r.json()).then(d=>{if(d.success)this.closest('.clay')?.remove()}) }" class="text-gray-300 hover:text-red-500 text-sm opacity-0 group-hover:opacity-100 transition p-1 flex-shrink-0 ml-2">×</button>
            @endif
        </div>
        @empty
        <div class="text-center py-12 clay bg-white">
            <p class="text-gray-500">Belum ada topik diskusi.</p>
        </div>
        @endforelse
    </div>

    <!-- New Topic Form -->
    @if($isMember)
    <div id="new-topic" class="clay bg-white p-6 mt-6" x-show="showTopicForm" x-cloak>
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">Buat Topik Baru</h3>
            <button @click="showTopicForm = false" class="text-gray-400 hover:text-gray-600 text-xl">×</button>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">Judul</label>
            <input type="text" x-model="topicTitle" class="w-full clay-input px-4 py-2.5 text-sm">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">Konten</label>
            <textarea x-model="topicContent" rows="4" class="w-full clay-input px-4 py-2.5 text-sm resize-none"></textarea>
        </div>
        <div class="mb-4">
            <label class="flex items-center space-x-2 cursor-pointer text-sm text-gray-600 hover:text-gray-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span x-text="topicMedia.length + ' gambar dipilih'"></span>
                <input type="file" accept="image/*" @change="topicMedia = Array.from($event.target.files)" class="hidden">
            </label>
            <template x-if="topicMedia.length > 0">
                <div class="flex flex-wrap gap-2 mt-2">
                    <template x-for="(f, i) in topicMedia" :key="i">
                        <div class="relative">
                            <img :src="URL.createObjectURL(f)" class="w-12 h-12 object-cover rounded-lg">
                            <button type="button" @click="topicMedia.splice(i, 1)" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs flex items-center justify-center">×</button>
                        </div>
                    </template>
                </div>
            </template>
        </div>
        <button @click="submitTopic({{ $forum->id }})" :disabled="!topicTitle.trim() || !topicContent.trim() || submitting" class="clay-btn bg-brand-peach font-bold py-2 px-6 text-sm disabled:opacity-50">
            <span x-show="!submitting">Buat Topik</span>
            <span x-show="submitting">...</span>
        </button>
    </div>
    @endif
</div>

<script>
    function forumPage() {
        return {
            showTopicForm: false,
            topicTitle: '',
            topicContent: '',
            topicMedia: [],
            submitting: false,
            submitTopic(forumId) {
                if (!this.topicTitle.trim() || !this.topicContent.trim() || this.submitting) return;
                this.submitting = true;
                const fd = new FormData();
                fd.append('title', this.topicTitle);
                fd.append('content', this.topicContent);
                for (const f of this.topicMedia) {
                    fd.append('media[]', f);
                }
                fetch('/forums/' + forumId + '/topics', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd
                })
                .then(r => r.json().then(res => ({ ok: r.ok, data: res })))
                .then(({ ok, data }) => {
                    if (ok && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.error || 'Gagal membuat topik');
                        this.submitting = false;
                    }
                })
                .catch(() => { alert('Gagal membuat topik'); this.submitting = false; });
            }
        }
    }
</script>
@endsection