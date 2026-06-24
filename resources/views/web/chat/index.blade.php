@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto" x-data="chatIndex()">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Chat</h1>
        <button @click="openNewChat = true" class="clay-btn px-5 py-2 bg-brand-peach font-bold text-sm flex items-center space-x-2">
            <span>+</span>
            <span>Percakapan Baru</span>
        </button>
    </div>

    @if($invitations->count() > 0)
    <div class="mb-6 space-y-2">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider px-1">Undangan Masuk</h3>
        @foreach($invitations as $invitation)
        <div class="clay bg-white p-4 flex items-center justify-between animate-fade-slide-up">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-brand-lilac rounded-full flex items-center justify-center font-bold text-gray-700">
                    {{ strtoupper(substr($invitation->conversation->name ?? 'G', 0, 1)) }}
                </div>
                <div>
                    <div class="font-bold text-gray-900 text-sm">{{ $invitation->conversation->name ?? 'Group' }}</div>
                    <div class="text-xs text-gray-500">Diundang oleh {{ $invitation->inviter->name }}</div>
                </div>
            </div>
            <div class="flex space-x-2">
                <button @click="respondInvitation({{ $invitation->id }}, 'accepted')" class="clay-sm px-3 py-1 bg-brand-mint text-sm font-bold">Terima</button>
                <button @click="respondInvitation({{ $invitation->id }}, 'declined')" class="clay-sm px-3 py-1 bg-gray-100 text-sm font-bold text-gray-600">Tolak</button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Daftar Percakapan -->
    <div class="space-y-3" x-ref="conversationList">
        <template x-for="conv in conversations" :key="conv.id">
            <a :href="'/chat/' + conv.id" class="block clay bg-white p-4 hover:shadow-lg transition-all duration-200 animate-fade-slide-up">
                <div class="flex items-center space-x-4">
                    <div class="relative flex-shrink-0">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center font-bold text-lg text-gray-700" :class="conv.type === 'group' ? 'bg-brand-lilac' : 'bg-brand-blue'">
                            <span x-text="conv.name ? conv.name.charAt(0).toUpperCase() : '?'"></span>
                        </div>
                        <template x-if="conv.type === 'group'">
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-white rounded-full flex items-center justify-center clay-sm">
                                <span class="text-xs">👥</span>
                            </div>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <h3 class="font-bold text-gray-900 truncate" x-text="conv.name || 'Percakapan'"></h3>
                            <span class="text-xs text-gray-400 flex-shrink-0 ml-2" x-text="formatTime(conv.last_message?.created_at || conv.created_at)"></span>
                        </div>
                        <p class="text-sm text-gray-500 truncate mt-0.5" x-text="conv.last_message?.body || (conv.type === 'group' ? 'Grup' : 'Mulai percakapan')"></p>
                    </div>
                </div>
            </a>
        </template>

        <template x-if="conversations.length === 0">
            <div class="text-center py-12 clay bg-white">
                <div class="text-4xl mb-3">💬</div>
                <p class="text-gray-500 font-medium">Belum ada percakapan</p>
                <p class="text-gray-400 text-sm mt-1">Mulai percakapan baru dengan teman kampusmu!</p>
            </div>
        </template>
    </div>

    <!-- Modal Percakapan Baru -->
    <div x-show="openNewChat" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/30" @click="openNewChat = false"></div>
        <div class="relative clay bg-white w-full max-w-md p-6 animate-fade-slide-up">
            <h2 class="text-xl font-bold mb-4">Percakapan Baru</h2>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Cari pengguna</label>
                <input type="text" x-model="searchQuery" @input.debounce.300ms="searchUsers" placeholder="Ketik username..." class="w-full clay-input px-4 py-2.5 text-sm">
            </div>

            <div x-show="searchResults.length > 0" class="mb-4 space-y-2 max-h-48 overflow-y-auto">
                <template x-for="user in searchResults" :key="user.id">
                    <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-xl transition cursor-pointer" @click="startDirectChat(user.id)">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-brand-mint rounded-full flex items-center justify-center font-bold text-gray-700">
                                <span x-text="user.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div>
                                <div class="font-bold text-sm" x-text="user.name"></div>
                                <div class="text-xs text-gray-500" x-text="'@' + user.username"></div>
                            </div>
                        </div>
                        <button class="clay-sm px-3 py-1 bg-brand-peach text-sm font-bold">Chat</button>
                    </div>
                </template>
            </div>

            <div x-show="searchQuery && searchResults.length === 0 && !searching" class="text-center text-sm text-gray-400 py-3">
                Tidak ada pengguna ditemukan
            </div>

            <div class="flex justify-end">
                <button @click="openNewChat = false" class="clay-sm px-4 py-2 bg-gray-100 text-sm font-bold text-gray-600">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
    function chatIndex() {
        return {
            openNewChat: false,
            searchQuery: '',
            searchResults: [],
            searching: false,
            conversations: {{ Js::from($conversations->values()) }},

            searchUsers() {
                if (!this.searchQuery || this.searchQuery.length < 1) {
                    this.searchResults = [];
                    return;
                }
                this.searching = true;
                fetch('/chat/users/search?q=' + encodeURIComponent(this.searchQuery))
                    .then(r => r.json())
                    .then(res => {
                        this.searchResults = res.data || [];
                    })
                    .catch(() => { this.searchResults = []; })
                    .finally(() => { this.searching = false; });
            },

            startDirectChat(userId) {
                fetch('/chat/create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ type: 'direct', target_user_id: userId })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.data) {
                        window.location.href = '/chat/' + res.data.id;
                    }
                })
                .catch(err => alert('Gagal membuat percakapan'));
            },

            respondInvitation(invitationId, status) {
                fetch('/invitations/' + invitationId + '/respond', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ status: status })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.data) {
                        window.location.reload();
                    }
                })
                .catch(err => alert('Gagal merespon undangan'));
            },

            formatTime(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                const now = new Date();
                const diff = now - d;
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));

                if (days === 0) {
                    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                } else if (days === 1) {
                    return 'Kemarin';
                } else if (days < 7) {
                    const daysOfWeek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
                    return daysOfWeek[d.getDay()];
                } else {
                    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                }
            }
        }
    }
</script>
@endsection
