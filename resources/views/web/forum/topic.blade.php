@extends('layouts.app')

@section('content')
<script>window.__topic = {!! json_encode($topicJson) !!}; window.__isMember = {{ $isMember ? 'true' : 'false' }};</script>
<div class="max-w-3xl mx-auto" x-data="topicPage()" x-init="initTopic(__topic, __isMember)">
    <div class="mb-4">
        <a href="{{ route('forums.show', $topic->forum_id) }}" class="text-sm text-gray-500 hover:underline">← Kembali ke forum</a>
    </div>

    <!-- Topic Header -->
    <div class="clay bg-white p-6 mb-6">
        <div class="flex items-start space-x-3 mb-4">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold bg-brand-peach flex-shrink-0">
                <span x-text="topic.user?.name?.charAt(0).toUpperCase() || '?'"></span>
            </div>
            <div class="flex-1">
                <h1 class="text-xl font-bold text-gray-900" x-text="topic.title"></h1>
                <div class="text-xs text-gray-500">
                    Oleh <strong x-text="topic.user?.name"></strong> &bull; <span x-text="formatTime(topic.created_at)"></span>
                </div>
            </div>
            <template x-if="topic.user_id === userId">
                <button @click="deleteTopic()" class="text-gray-300 hover:text-red-500 text-sm flex-shrink-0">×</button>
            </template>
        </div>
        <div class="text-gray-700 whitespace-pre-wrap" x-text="topic.content"></div>
        <template x-if="topic.file_url">
            <img :src="topic.file_url" class="rounded-2xl w-full object-cover max-h-96 mt-4" loading="lazy">
        </template>
    </div>

    <!-- Comments -->
    <div class="space-y-4">
        <template x-for="comment in comments" :key="comment.id">
            <div class="clay-sm bg-white p-4">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs bg-brand-lilac flex-shrink-0">
                        <span x-text="comment.user?.name?.charAt(0).toUpperCase() || '?'"></span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-sm" x-text="comment.user?.name"></span>
                                <span class="text-xs text-gray-400" x-text="formatTime(comment.created_at)"></span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <button @click="setReply(comment)" class="text-xs text-gray-400 hover:text-brand-blue font-medium">Balas</button>
                                <template x-if="comment.user_id === userId">
                                    <button @click="deleteComment(comment.id)" class="text-gray-300 hover:text-red-500 text-sm ml-1">×</button>
                                </template>
                            </div>
                        </div>
                        <p class="text-sm text-gray-700 mt-1" x-text="comment.content"></p>
                        <template x-if="comment.file_url">
                            <img :src="comment.file_url" class="rounded-xl max-h-48 w-full object-cover mt-2" loading="lazy">
                        </template>

                        <template x-if="comment.replies && comment.replies.length > 0">
                            <div class="mt-3 ml-4 space-y-2 border-l-2 border-gray-100 pl-4">
                                <template x-for="reply in comment.replies" :key="reply.id">
                                    <div class="flex items-start space-x-2 group">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] bg-brand-mint flex-shrink-0">
                                            <span x-text="reply.user?.name?.charAt(0).toUpperCase() || '?'"></span>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-xs" x-text="reply.user?.name"></span>
                                                    <span class="text-[10px] text-gray-400" x-text="formatTime(reply.created_at)"></span>
                                                </div>
                                                <template x-if="reply.user_id === userId">
                                                    <button @click="deleteComment(reply.id)" class="text-gray-300 hover:text-red-500 text-xs opacity-0 group-hover:opacity-100 transition">×</button>
                                                </template>
                                            </div>
                                            <p class="text-xs text-gray-700" x-text="reply.content"></p>
                                            <template x-if="reply.file_url">
                                                <img :src="reply.file_url" class="rounded-lg max-h-32 object-cover mt-1" loading="lazy">
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="comments.length === 0">
            <div class="text-center py-8 clay-sm bg-white">
                <p class="text-gray-400 text-sm">Belum ada komentar.</p>
            </div>
        </template>
    </div>

    <!-- Reply Preview -->
    <template x-if="replyToComment">
        <div class="flex items-center justify-between clay-sm bg-white p-2 mt-4 text-sm">
            <span class="text-gray-500 truncate">Membalas <span class="font-bold" x-text="replyToComment.user?.name"></span></span>
            <button @click="replyToComment = null" class="text-gray-400 hover:text-red-500 ml-2 text-lg leading-none">×</button>
        </div>
    </template>

    <!-- Comment Input -->
    <template x-if="isMember">
        <div class="mt-4">
            <div class="flex space-x-2">
                <input type="text" x-model="newComment" id="comment-input" @keydown.enter.prevent="addComment()" placeholder="Tulis komentar..." class="flex-1 clay-input px-3 py-2 text-sm">
                <label class="clay-sm px-2 py-2 bg-gray-50 cursor-pointer hover:bg-gray-100 transition flex-shrink-0 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <input type="file" accept="image/*" @change="commentMedia = Array.from($event.target.files)" class="hidden">
                </label>
                <button @click="addComment()" :disabled="(!newComment.trim() && commentMedia.length === 0) || submitting" class="clay-sm px-3 py-2 bg-brand-blue text-sm font-bold disabled:opacity-50">
                    <span x-show="!submitting">Kirim</span>
                    <span x-show="submitting">...</span>
                </button>
            </div>
            <template x-if="commentMedia.length > 0">
                <div class="flex flex-wrap gap-2 mt-2">
                    <template x-for="(f, i) in commentMedia" :key="i">
                        <div class="relative">
                            <img :src="URL.createObjectURL(f)" class="w-12 h-12 object-cover rounded-lg">
                            <button type="button" @click="commentMedia.splice(i, 1)" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs flex items-center justify-center">×</button>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </template>

    <template x-if="!isMember">
        <div class="text-center py-6 clay bg-white mt-4">
            <p class="text-gray-500 text-sm">Gabung forum ini untuk bisa berkomentar.</p>
        </div>
    </template>
</div>

<script>
    function topicPage() {
        return {
            topic: {},
            comments: [],
            isMember: false,
            userId: {{ Auth::id() }},
            newComment: '',
            submitting: false,
            replyToComment: null,
            commentMedia: [],

            initTopic(data, member) {
                this.topic = data;
                this.comments = data.comments || [];
                this.isMember = member;
            },

            formatTime(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                const now = new Date();
                const diff = now - d;
                const mins = Math.floor(diff / 60000);
                const hours = Math.floor(diff / 3600000);
                const days = Math.floor(diff / 86400000);
                if (mins < 1) return 'Baru saja';
                if (mins < 60) return mins + 'm yang lalu';
                if (hours < 24) return hours + 'j yang lalu';
                if (days < 7) return days + 'h yang lalu';
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            },

            setReply(comment) {
                this.replyToComment = comment;
                this.$nextTick(() => {
                    const el = document.querySelector('#comment-input');
                    if (el) el.focus();
                });
            },

            addComment() {
                if ((!this.newComment.trim() && this.commentMedia.length === 0) || this.submitting) return;
                this.submitting = true;
                const fd = new FormData();
                fd.append('content', this.newComment || '(gambar)');
                if (this.replyToComment) {
                    fd.append('parent_comment_id', this.replyToComment.id);
                }
                for (const f of this.commentMedia) {
                    fd.append('media[]', f);
                }
                fetch('/topics/' + this.topic.id + '/comments', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd
                })
                .then(r => r.json().then(res => ({ ok: r.ok, data: res })))
                .then(({ ok, data }) => {
                    if (ok && data.data) {
                        const c = data.data;
                        if (c.parent_comment_id) {
                            const parent = this.comments.find(cmt => cmt.id === c.parent_comment_id);
                            if (parent) {
                                if (!parent.replies) parent.replies = [];
                                parent.replies.push(c);
                            }
                        } else {
                            c.replies = [];
                            this.comments.push(c);
                        }
                        this.newComment = '';
                        this.commentMedia = [];
                        this.replyToComment = null;
                    } else {
                        alert(data.error || 'Gagal menambahkan komentar');
                    }
                    this.submitting = false;
                })
                .catch(() => { alert('Gagal menambahkan komentar'); this.submitting = false; });
            },

            deleteComment(commentId) {
                if (!confirm('Hapus komentar ini?')) return;
                fetch('/topics/' + this.topic.id + '/comments/' + commentId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(r => r.json().then(res => ({ ok: r.ok, data: res })))
                .then(({ ok, data }) => {
                    if (ok && data.success) {
                        this.comments = this.comments.map(c => {
                            if (c.replies && c.replies.some(r => r.id === commentId)) {
                                return { ...c, replies: c.replies.filter(r => r.id !== commentId) };
                            }
                            return c;
                        });
                        this.comments = this.comments.filter(c => c.id !== commentId);
                    } else {
                        alert(data.error || 'Gagal menghapus komentar');
                    }
                })
                .catch(() => alert('Gagal menghapus komentar'));
            },

            deleteTopic() {
                if (!confirm('Hapus topik ini?')) return;
                fetch('/forums/' + this.topic.forum_id + '/topics/' + this.topic.id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(r => r.json().then(res => ({ ok: r.ok, data: res })))
                .then(({ ok, data }) => {
                    if (ok && data.success) {
                        window.location.href = '/forums/' + this.topic.forum_id;
                    } else {
                        alert(data.error || 'Gagal menghapus topik');
                    }
                })
                .catch(() => alert('Gagal menghapus topik'));
            }
        }
    }
</script>
@endsection