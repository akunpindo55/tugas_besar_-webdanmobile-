@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto" x-data="chatRoom({{ $currentConversation->id }})" x-init="init()">
    <!-- Split Layout -->
    <div class="flex gap-4 h-[calc(100vh-8rem)]">
        <!-- Sidebar Daftar Percakapan -->
        <div class="w-72 flex-shrink-0 overflow-y-auto space-y-2 hidden md:block">
            <a href="{{ route('chat.index') }}" class="block clay-sm bg-white p-3 mb-3 text-center font-bold text-sm hover:bg-gray-50 transition">
                Semua Percakapan
            </a>
            @foreach($conversations as $conv)
            <a href="{{ route('chat.show', $conv->id) }}"
               class="block clay-sm bg-white p-3 transition hover:shadow-md @if((int)$currentConversation->id === (int)$conv->id) ring-2 ring-brand-peach @endif">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0 {{ $conv->type === 'group' ? 'bg-brand-lilac' : 'bg-brand-blue' }}">
                        {{ strtoupper(substr($conv->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-sm truncate">{{ $conv->type === 'direct' ? $conv->name : $conv->name }}</div>
                        <div class="text-xs text-gray-400 truncate">{{ $conv->last_message?->body ?? '...' }}</div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <!-- Chat Room -->
        <div class="flex-1 flex flex-col clay bg-white overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-3 border-b-2 border-gray-100">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('chat.index') }}" class="md:hidden text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold {{ $currentConversation->type === 'group' ? 'bg-brand-lilac' : 'bg-brand-blue' }}">
                        {{ strtoupper(substr($currentConversation->name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">{{ $currentConversation->name ?? 'Percakapan' }}</h2>
                        @if($currentConversation->type === 'group')
                        <p class="text-xs text-gray-500">{{ $currentConversation->members->count() }} anggota</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false" class="clay-sm w-8 h-8 bg-gray-50 text-gray-600 flex items-center justify-center text-lg font-bold leading-none">⋮</button>
                        <div x-show="open" x-cloak @click="open = false" class="absolute right-0 top-full mt-1 clay bg-white p-2 min-w-[160px] z-50 shadow-lg">
                            @if($currentConversation->type === 'group')
                            <button @click="showGroupInfo = !showGroupInfo" class="w-full text-left px-3 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 rounded-xl transition">Info</button>
                            <button @click="leaveGroup()" class="w-full text-left px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-50 rounded-xl transition">Keluar</button>
                            @endif
                            @if($currentConversation->created_by === Auth::id() || $currentConversation->type === 'direct')
                            <button @click="deleteConversation()" class="w-full text-left px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-50 rounded-xl transition">Hapus</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-5 space-y-3" x-ref="messagesContainer" @scroll="onScroll()">
                <template x-if="loading">
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-sm">Memuat pesan...</div>
                    </div>
                </template>

                <template x-if="hasMore">
                    <div class="text-center py-2">
                        <button @click="loadMore()" class="text-sm text-brand-blue font-bold hover:underline">Muat lebih lama...</button>
                    </div>
                </template>

                <template x-for="(msg, idx) in messages" :key="msg.id">
                    <div class="flex" :class="msg.sender_id === {{ Auth::id() }} ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[75%]" :class="msg.sender_id === {{ Auth::id() }} ? 'order-1' : 'order-1'">
                            <!-- Sender name (group only) -->
                            <template x-if="{{ $currentConversation->type === 'group' ? 'true' : 'false' }} && msg.sender_id !== {{ Auth::id() }} && msg.message_type !== 'system'">
                                <div class="text-xs font-bold text-gray-500 mb-1 ml-1" x-text="msg.sender?.name || 'Unknown'"></div>
                            </template>

                            <!-- System Message -->
                            <template x-if="msg.message_type === 'system'">
                                <div class="text-center">
                                    <span class="text-xs text-gray-400 italic bg-gray-50 px-3 py-1 rounded-full" x-text="msg.body"></span>
                                </div>
                            </template>

                            <!-- Regular Message -->
                            <template x-if="msg.message_type !== 'system'">
                                <div class="relative group">
                                    <div :class="msg.sender_id === {{ Auth::id() }} ? 'clay-bubble-self bg-brand-peach bg-opacity-30' : 'clay-bubble-other bg-gray-50'"
                                         class="px-4 py-2.5">
                                        <!-- Reply Reference -->
                                        <template x-if="msg.replyTo">
                                            <div class="text-xs mb-1.5 px-2 py-1 rounded-lg bg-black/5 border-l-2 border-gray-400">
                                                <span class="font-bold text-gray-500">Membalas:</span>
                                                <p class="truncate text-gray-500" x-text="msg.replyTo.body || 'File'"></p>
                                            </div>
                                        </template>
                                        <p class="text-sm text-gray-800 whitespace-pre-wrap break-words" x-text="msg.body"></p>
                                        <!-- File Attachment -->
                                        <template x-if="msg.file_url">
                                            <div class="mt-2">
                                                <template x-if="msg.message_type === 'image'">
                                                    <img :src="msg.file_url" class="rounded-2xl max-h-60 w-full object-cover cursor-pointer" @click="window.open(msg.file_url, '_blank')" loading="lazy">
                                                </template>
                                                <template x-if="msg.message_type !== 'image'">
                                                    <a :href="msg.file_url" target="_blank" class="clay-sm flex items-center space-x-2 px-3 py-2 bg-white text-sm">
                                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                        <span class="text-blue-600 underline">Lihat file</span>
                                                    </a>
                                                </template>
                                            </div>
                                        </template>
                                        <div class="flex items-center justify-end space-x-1 mt-1">
                                            <span class="text-[10px] text-gray-400" x-text="formatTime(msg.created_at)"></span>
                                            <template x-if="msg.sender_id === {{ Auth::id() }}">
                                                <div class="flex items-center space-x-0.5">
                                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M6 12l4 4 8-8"/>
                                                    </svg>
                                                    <template x-if="msg.reads?.filter(r => r.user_id !== userId).length > 0">
                                                        <svg class="w-3 h-3 text-brand-blue" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M6 12l4 4 8-8"/>
                                                        </svg>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <!-- Actions -->
                                    <div class="absolute -top-2 right-0 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="setReply(msg)" class="bg-white clay-sm w-6 h-6 flex items-center justify-center text-xs text-gray-500 hover:text-brand-blue" title="Balas">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                                        </button>
                                                        <template x-if="msg.sender_id === {{ Auth::id() }}">
                                        <button @click="deleteMessage(msg.id)" class="bg-white clay-sm w-6 h-6 flex items-center justify-center text-xs text-red-500 hover:text-red-700" title="Hapus">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                        </template>
                                                    </div>
                                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="!loading && messages.length === 0">
                    <div class="text-center py-12">
                        <p class="text-gray-400 text-sm">Belum ada pesan. Kirim pesan pertama!</p>
                    </div>
                </template>

                <div x-ref="scrollAnchor"></div>
            </div>

            <!-- Reply Preview -->
            <div x-show="replyTo" class="border-t-2 border-gray-100 px-4 py-2 flex items-center justify-between bg-brand-lilac bg-opacity-10" x-cloak>
                <div class="text-sm">
                    <span class="font-bold text-xs text-gray-500">Membalas:</span>
                    <p class="text-sm text-gray-600 truncate max-w-xs" x-text="replyTo?.body || 'Pesan'"></p>
                </div>
                <button @click="replyTo = null" class="text-gray-400 hover:text-red-500 text-lg">×</button>
            </div>

            <!-- Input Bar -->
            <div class="border-t-2 border-gray-100 p-4">
                <form @submit.prevent="sendMessage()" class="flex items-end space-x-3">
                    <div class="flex-1 flex items-end space-x-2">
                        <label class="clay-sm p-2 bg-gray-50 cursor-pointer hover:bg-gray-100 transition flex-shrink-0">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <input type="file" accept="image/*,video/*,.pdf,.doc,.docx" class="hidden" @change="handleFileSelect($event)">
                        </label>
                        <textarea x-model="newMessage" @keydown.enter.prevent="!$event.shiftKey && sendMessage()" rows="1"
                            x-init="$el.addEventListener('input', () => { $el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 128) + 'px' })"
                            class="flex-1 clay-input px-4 py-2.5 text-sm resize-none max-h-32"
                            placeholder="Tulis pesan..." x-ref="messageInput"></textarea>
                    </div>
                    <button type="submit" :disabled="(!newMessage.trim() && !selectedFile) || sending"
                        class="clay-btn px-5 py-2.5 bg-brand-peach font-bold text-sm disabled:opacity-50 flex items-center space-x-2">
                        <span x-show="!sending">Kirim</span>
                        <span x-show="sending">...</span>
                    </button>
                </form>
                <!-- File preview -->
                <template x-if="selectedFile">
                    <div class="mt-2 clay-sm bg-gray-50 p-2 flex items-center justify-between text-sm">
                        <span class="truncate" x-text="selectedFile.name"></span>
                        <button @click="selectedFile = null" class="text-red-500 ml-2 font-bold">×</button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Group Info Modal -->
    <div x-show="showGroupInfo" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/30" @click="showGroupInfo = false"></div>
        <div class="relative clay bg-white w-full max-w-md p-6 animate-fade-slide-up">
            <h2 class="text-xl font-bold mb-4">Info Grup</h2>
            <p class="text-sm text-gray-600 mb-4" x-text="'{{ $currentConversation->description ?? 'Tidak ada deskripsi' }}'"></p>
            <h3 class="font-bold text-sm text-gray-700 mb-2">Anggota ({{ $currentConversation->members->count() }})</h3>
            <div class="space-y-2 max-h-48 overflow-y-auto mb-4">
                @foreach($currentConversation->members as $member)
                <div class="flex items-center space-x-3 p-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs bg-brand-mint">
                        {{ strtoupper(substr($member->name, 0, 1)) }}
                    </div>
                    <div>
                        <span class="text-sm font-bold">{{ $member->name }}</span>
                        @if($member->pivot->role === 'owner')
                        <span class="text-xs text-gray-400 ml-1">Pemilik</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Invite -->
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Undang anggota</label>
                <div class="flex space-x-2">
                    <input type="text" x-model="inviteQuery" @input.debounce.300ms="searchInviteUsers" placeholder="Cari user..." class="flex-1 clay-input px-3 py-2 text-sm">
                    <button @click="sendInvite()" :disabled="!selectedInviteUser" class="clay-sm px-3 py-2 bg-brand-mint text-sm font-bold disabled:opacity-50">Undang</button>
                </div>
                <div x-show="inviteResults.length > 0" class="mt-2 space-y-1 max-h-32 overflow-y-auto">
                    <template x-for="user in inviteResults" :key="user.id">
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-xl transition cursor-pointer" @click="selectedInviteUser = user">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs bg-brand-lilac" x-text="user.name.charAt(0).toUpperCase()"></div>
                                <span class="text-sm" x-text="user.name"></span>
                            </div>
                            <div x-show="selectedInviteUser?.id === user.id" class="text-brand-mint text-sm">✓</div>
                        </div>
                    </template>
                </div>
                <div x-show="selectedInviteUser" class="mt-2 clay-sm bg-brand-lilac bg-opacity-20 p-2 text-sm flex items-center justify-between">
                    <span x-text="'Dipilih: ' + selectedInviteUser?.name"></span>
                    <button @click="selectedInviteUser = null" class="text-red-500 text-xs font-bold">×</button>
                </div>
            </div>

            <div class="flex justify-end">
                <button @click="showGroupInfo = false" class="clay-sm px-4 py-2 bg-gray-100 text-sm font-bold text-gray-600">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function chatRoom(conversationId) {
        return {
            conversationId: conversationId,
            messages: [],
            newMessage: '',
            loading: true,
            sending: false,
            hasMore: true,
            cursor: null,
            showGroupInfo: false,
            inviteQuery: '',
            inviteResults: [],
            selectedInviteUser: null,
            selectedFile: null,
            replyTo: null,
            userId: {{ Auth::id() }},

            init() {
                this.loadMessages();
                this.markAllRead();
                this.initEcho();
            },

            initEcho() {
                if (typeof Echo !== 'undefined') {
                    Echo.private('conversation.' + this.conversationId)
                        .listen('MessageSent', (e) => {
                            if (e.message && e.message.sender_id !== this.userId) {
                                const exists = this.messages.some(m => m.id === e.message.id);
                                if (!exists) {
                                    this.messages.push(e.message);
                                    this.$nextTick(() => this.scrollToBottom());
                                }
                            }
                        })
                        .listen('MessageRead', (e) => {
                            if (e.messageIds) {
                                this.messages.forEach(m => {
                                    if (e.messageIds.includes(m.id)) {
                                        if (!m.reads) m.reads = [];
                                        m.reads.push({ user_id: e.userId, read_at: e.readAt });
                                    }
                                });
                            }
                        });
                }
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) this.selectedFile = file;
                event.target.value = '';
            },

            setReply(message) {
                this.replyTo = message;
                this.$refs.messageInput?.focus();
            },

            loadMessages() {
                this.loading = true;
                fetch('/chat/' + this.conversationId + '/messages?limit=30')
                    .then(r => r.json())
                    .then(res => {
                        this.messages = res.data || [];
                        this.cursor = res.prev_cursor || null;
                        this.hasMore = res.has_more !== undefined ? res.has_more : !!this.cursor;
                        this.loading = false;
                        this.$nextTick(() => this.scrollToBottom());
                    })
                    .catch(() => { this.loading = false; });
            },

            loadMore() {
                if (!this.cursor) return;
                const url = '/chat/' + this.conversationId + '/messages?limit=30&cursor=' + this.cursor;
                fetch(url)
                    .then(r => r.json())
                    .then(res => {
                        const oldHeight = this.$refs.messagesContainer.scrollHeight;
                        this.messages = [...(res.data || []), ...this.messages];
                        this.cursor = res.prev_cursor || null;
                        this.hasMore = res.has_more !== undefined ? res.has_more : !!this.cursor;
                        this.$nextTick(() => {
                            const newHeight = this.$refs.messagesContainer.scrollHeight;
                            this.$refs.messagesContainer.scrollTop = newHeight - oldHeight;
                        });
                    })
                    .catch(() => {});
            },

            sendMessage() {
                if ((!this.newMessage.trim() && !this.selectedFile) || this.sending) return;
                this.sending = true;

                const formData = new FormData();
                if (this.newMessage.trim()) {
                    formData.append('content', this.newMessage);
                    formData.append('message_type', 'text');
                } else if (this.selectedFile) {
                    formData.append('file', this.selectedFile);
                    formData.append('message_type', this.selectedFile.type.startsWith('image') ? 'image' : 'file');
                    formData.append('content', this.selectedFile.name);
                }
                if (this.replyTo) {
                    formData.append('reply_to', this.replyTo.id);
                }

                fetch('/chat/' + this.conversationId + '/messages', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.data) {
                        this.messages.push(res.data);
                        this.newMessage = '';
                        this.selectedFile = null;
                        this.replyTo = null;
                        this.$nextTick(() => this.scrollToBottom());
                    }
                })
                .catch(err => alert('Gagal mengirim pesan'))
                .finally(() => { this.sending = false; });
            },

            deleteMessage(messageId) {
                if (!confirm('Hapus pesan ini?')) return;
                fetch('/chat/messages/' + messageId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        this.messages = this.messages.filter(m => m.id !== messageId);
                    }
                })
                .catch(() => alert('Gagal menghapus pesan'));
            },

            markAllRead() {
                fetch('/chat/' + this.conversationId + '/read-all', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                }).catch(() => {});
            },

            onScroll() {
                const el = this.$refs.messagesContainer;
                if (el && el.scrollTop < 100 && this.hasMore && !this.loading) {
                    this.loadMore();
                }
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.messagesContainer;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },

            leaveGroup() {
                if (!confirm('Yakin ingin keluar dari grup ini?')) return;
                fetch('/chat/' + this.conversationId + '/leave', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) window.location.href = '/chat';
                })
                .catch(() => alert('Gagal keluar grup'));
            },

            deleteConversation() {
                if (!confirm('Hapus seluruh percakapan ini untuk semua orang?')) return;
                fetch('/chat/' + this.conversationId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) window.location.href = '/chat';
                    else alert(res.error || 'Gagal menghapus percakapan');
                })
                .catch(() => alert('Gagal menghapus percakapan'));
            },

            searchInviteUsers() {
                if (!this.inviteQuery || this.inviteQuery.length < 1) {
                    this.inviteResults = [];
                    return;
                }
                fetch('/chat/users/search?q=' + encodeURIComponent(this.inviteQuery))
                    .then(r => r.json())
                    .then(res => { this.inviteResults = res.data || []; })
                    .catch(() => { this.inviteResults = []; });
            },

            sendInvite() {
                if (!this.selectedInviteUser) return;
                fetch('/chat/' + this.conversationId + '/invite', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ invited_user_id: this.selectedInviteUser.id })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.data) {
                        alert('Undangan berhasil dikirim!');
                        this.selectedInviteUser = null;
                        this.inviteQuery = '';
                        this.inviteResults = [];
                    } else if (res.error) {
                        alert(res.error);
                    }
                })
                .catch(() => alert('Gagal mengundang'));
            },

            formatTime(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            }
        }
    }
</script>
@endsection
