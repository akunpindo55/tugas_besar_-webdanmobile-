@extends('layouts.app')

@section('content')
<script>window.__posts = {!! json_encode($postsJson) !!}; window.__meta = {!! json_encode($meta) !!};</script>
<div class="max-w-2xl mx-auto" x-data="feed()" x-init="initFeed(__posts, __meta)">
    <!-- Create Post Form -->
    <div class="clay bg-white p-6 mb-8" x-data="{
        content: '',
        isSubmitting: false,
        mediaFiles: [],
        submitPost() {
            if(!this.content.trim()) return;
            this.isSubmitting = true;
            const fd = new FormData();
            fd.append('content', this.content);
            fd.append('visibility', 'public');
            for (const f of this.mediaFiles || []) {
                fd.append('media[]', f);
            }
            fetch('/posts', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: fd
            })
            .then(r => r.json().then(res => ({ ok: r.ok, data: res })))
            .then(({ ok, data }) => {
                if (ok && data.data) {
                    this.content = '';
                    this.mediaFiles = [];
                    window.feedInstance.prependPost(data.data);
                } else {
                    alert(data.error || data.message || 'Gagal membuat postingan');
                }
                this.isSubmitting = false;
            })
            .catch(() => { alert('Gagal membuat postingan'); this.isSubmitting = false; });
        }
    }">
        <h3 class="text-lg font-bold mb-4">Buat Postingan</h3>
        <textarea x-model="content" class="w-full clay-input px-4 py-3 mb-4 resize-none" rows="3" placeholder="Apa kejadian seru di kampus hari ini?"></textarea>

        <div class="mb-3">
            <label class="flex items-center space-x-2 cursor-pointer text-sm text-gray-600 hover:text-gray-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span x-text="(mediaFiles || []).length + ' file dipilih'"></span>
                <input type="file" multiple accept="image/*,video/*" @change="mediaFiles = Array.from($event.target.files)" class="hidden">
            </label>
            <template x-if="mediaFiles && mediaFiles.length > 0">
                <div class="flex flex-wrap gap-2 mt-2">
                    <template x-for="(f, i) in mediaFiles" :key="i">
                        <div class="relative">
                            <template x-if="f.type.startsWith('image/')">
                                <img :src="URL.createObjectURL(f)" class="w-16 h-16 object-cover rounded-xl">
                            </template>
                            <template x-if="!f.type.startsWith('image/')">
                                <div class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center text-xs text-gray-500">Video</div>
                            </template>
                            <button type="button" @click="mediaFiles.splice(i, 1)" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center">×</button>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <div class="flex justify-end">
            <button @click="submitPost()" :disabled="isSubmitting || !content.trim()" class="clay-btn bg-brand-peach font-bold py-2 px-6 text-sm disabled:opacity-50">
                <span x-show="!isSubmitting">Kirim</span>
                <span x-show="isSubmitting">Loading...</span>
            </button>
        </div>
    </div>

    <!-- Feed -->
    <div class="space-y-6">
        <template x-for="post in posts" :key="post.id">
            <div class="clay bg-white p-6 animate-fade-slide-up">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center space-x-3">
                        <a :href="'/profile/' + (post.user?.username || '')" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-gray-700"
                            :class="'bg-brand-' + ['blue','mint','peach','lilac'][post.user?.id % 4]">
                            <span x-text="post.user?.name ? post.user.name.charAt(0).toUpperCase() : '?'"></span>
                        </a>
                        <div>
                            <a :href="'/profile/' + (post.user?.username || '')" class="font-bold text-gray-900 hover:underline" x-text="post.user?.name || 'Unknown'"></a>
                            <div class="text-xs text-gray-500">
                                <span x-text="formatTime(post.created_at)"></span>
                                <span> &bull; </span>
                                <span x-text="post.visibility === 'public' ? 'Publik' : 'Internal'"></span>
                            </div>
                        </div>
                    </div>
                    <template x-if="post.user_id === {{ Auth::id() }}">
                        <button @click="deletePost(post.id)" class="text-gray-400 hover:text-red-500 text-sm clay-sm px-2 py-1">×</button>
                    </template>
                </div>

                <p class="text-gray-800 whitespace-pre-wrap mb-4" x-text="post.content"></p>

                <!-- Post Media -->
                <template x-if="post.media && post.media.length > 0">
                    <div class="mb-4 space-y-2">
                        <template x-for="m in post.media" :key="m.id">
                            <template x-if="m.media_type === 'image'">
                                <img :src="m.media_url" class="rounded-2xl w-full object-cover max-h-96" loading="lazy">
                            </template>
                            <template x-if="m.media_type === 'video'">
                                <video :src="m.media_url" class="rounded-2xl w-full max-h-96" controls></video>
                            </template>
                        </template>
                    </div>
                </template>

                <!-- Actions -->
                <div class="flex items-center space-x-6 text-sm border-t-2 border-gray-100 pt-4">
                    <!-- Reactions -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="toggleReaction(post.id, 'like'); open = false" class="flex items-center space-x-1.5 hover:scale-105 transition" :class="post.user_reaction === 'like' ? 'text-red-500' : 'text-gray-500'">
                            <span>❤️</span>
                            <span class="font-bold text-xs" x-text="post.reactions?.filter(r => r.reaction_type === 'like').length || 0"></span>
                        </button>
                    </div>
                    <button @click="toggleComments(post.id)" class="flex items-center space-x-1.5 text-gray-500 hover:text-brand-blue transition">
                        <span>💬</span>
                        <span class="font-bold text-xs" x-text="post.comments?.length || 0"></span>
                    </button>
                </div>

                <!-- Comments Section -->
                <div x-show="openComments === post.id" x-cloak class="mt-4 space-y-3 border-t-2 border-gray-100 pt-4">
                    <template x-for="comment in post.comments || []" :key="comment.id">
                        <div class="flex items-start space-x-2 group">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs mt-0.5 flex-shrink-0 bg-brand-lilac">
                                <span x-text="comment.user?.name?.charAt(0).toUpperCase() || '?'"></span>
                            </div>
                            <div class="flex-1 bg-gray-50 rounded-2xl px-3 py-2">
                                <div class="flex justify-between items-start">
                                    <div class="font-bold text-xs" x-text="comment.user?.name || 'Unknown'"></div>
                                    <template x-if="comment.user_id === {{ Auth::id() }}">
                                        <button @click="deleteComment(post.id, comment.id)" class="text-gray-400 hover:text-red-500 text-xs opacity-0 group-hover:opacity-100 transition ml-2">×</button>
                                    </template>
                                </div>
                                <p class="text-sm text-gray-700" x-text="comment.comment"></p>
                                <button @click="replyToComment(post.id, comment)" class="text-xs text-gray-400 hover:text-brand-blue mt-1 font-medium">Balas</button>
                            </div>
                        </div>
                    </template>
                    <div class="flex space-x-2">
                        <input type="text" :id="'comment-input-' + post.id" x-model="commentTexts[post.id]" @keydown.enter.prevent="addComment(post.id)" placeholder="Tulis komentar..." class="flex-1 clay-input px-3 py-2 text-sm">
                        <button @click="addComment(post.id)" class="clay-sm px-3 py-2 bg-brand-blue text-sm font-bold">Kirim</button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="!loading && posts.length === 0">
            <div class="text-center py-12 clay bg-white">
                <div class="text-4xl mb-3">📝</div>
                <p class="text-gray-500 font-medium">Belum ada postingan</p>
                <p class="text-gray-400 text-sm mt-1">Jadilah yang pertama berbagi cerita!</p>
            </div>
        </template>

        <template x-if="loading">
            <div class="text-center py-8">
                <div class="text-gray-400 text-sm">Memuat postingan...</div>
            </div>
        </template>

        <template x-if="hasMore">
            <div class="text-center py-4">
                <button @click="loadMore()" class="clay-btn px-6 py-2 bg-white text-sm font-bold text-gray-600">Muat lebih banyak</button>
            </div>
        </template>
    </div>
</div>

<script>
    function feed() {
        return {
            posts: [],
            loading: true,
            hasMore: true,
            page: 1,
            openComments: null,
            commentTexts: {},

            initFeed(initialPosts, initialMeta) {
                window.feedInstance = this;
                if (initialPosts) {
                    this.posts = (initialPosts || []).map(p => ({
                        ...p,
                        user_reaction: p.reactions?.find(r => r.user_id === {{ Auth::id() }})?.reaction_type || null
                    }));
                    this.hasMore = initialMeta?.current_page < initialMeta?.last_page;
                    this.page = initialMeta?.current_page || 1;
                    this.loading = false;
                } else {
                    this.loadPosts();
                }
            },

            loadPosts() {
                this.loading = true;
                fetch('/feed?page=' + this.page)
                    .then(r => r.json())
                    .then(res => {
                        const newPosts = (res.data || []).map(p => ({
                            ...p,
                            user_reaction: p.reactions?.find(r => r.user_id === {{ Auth::id() }})?.reaction_type || null
                        }));
                        if (this.page === 1) {
                            this.posts = newPosts;
                        } else {
                            this.posts = [...this.posts, ...newPosts];
                        }
                        this.hasMore = res.meta?.current_page < res.meta?.last_page;
                        this.loading = false;
                    })
                    .catch(() => { this.loading = false; });
            },

            loadMore() {
                this.page++;
                this.loadPosts();
            },

            prependPost(post) {
                this.posts.unshift({
                    ...post,
                    user_reaction: null,
                    comments: []
                });
            },

            deletePost(postId) {
                if (!confirm('Hapus postingan ini?')) return;
                fetch('/posts/' + postId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json().then(res => ({ ok: r.ok, data: res })))
                .then(({ ok, data }) => {
                    if (ok && data.success) {
                        this.posts = this.posts.filter(p => p.id !== postId);
                    } else {
                        alert(data.error || 'Gagal menghapus postingan');
                    }
                })
                .catch(() => alert('Gagal menghapus postingan'));
            },

            toggleComments(postId) {
                this.openComments = this.openComments === postId ? null : postId;
            },

            replyToComment(postId, comment) {
                this.commentTexts[postId] = '@' + (comment.user?.name || comment.user?.username || '') + ' ';
                this.openComments = postId;
                this.$nextTick(() => {
                    const el = document.querySelector('#comment-input-' + postId);
                    if (el) { el.focus(); el.setSelectionRange(el.value.length, el.value.length); }
                });
            },

            addComment(postId) {
                const text = this.commentTexts[postId];
                if (!text || !text.trim()) return;
                fetch('/posts/' + postId + '/comments', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ comment: text })
                })
                .then(r => r.json().then(res => ({ ok: r.ok, data: res })))
                .then(({ ok, data }) => {
                    if (ok && data.data) {
                        const post = this.posts.find(p => p.id === postId);
                        if (post) {
                            if (!post.comments) post.comments = [];
                            post.comments.push(data.data);
                        }
                        this.commentTexts[postId] = '';
                    } else {
                        alert(data.error || data.message || 'Gagal menambahkan komentar');
                    }
                })
                .catch(() => alert('Gagal menambahkan komentar'));
            },

            deleteComment(postId, commentId) {
                if (!confirm('Hapus komentar ini?')) return;
                fetch('/posts/' + postId + '/comments/' + commentId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json().then(res => ({ ok: r.ok, data: res })))
                .then(({ ok, data }) => {
                    if (ok && data.success) {
                        const post = this.posts.find(p => p.id === postId);
                        if (post && post.comments) {
                            post.comments = post.comments.filter(c => c.id !== commentId);
                        }
                    } else {
                        alert(data.error || 'Gagal menghapus komentar');
                    }
                })
                .catch(() => alert('Gagal menghapus komentar'));
            },

            toggleReaction(postId, type) {
                fetch('/posts/' + postId + '/reactions', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ reaction_type: type })
                })
                .then(r => r.json().then(res => ({ ok: r.ok, data: res })))
                .then(({ ok, data }) => {
                    if (ok && data.reactions) {
                        const post = this.posts.find(p => p.id === postId);
                        if (post) {
                            const hasReaction = data.data !== null;
                            if (hasReaction) {
                                post.user_reaction = type;
                            } else {
                                post.user_reaction = null;
                            }
                            post.reactions = [];
                            Object.entries(data.reactions).forEach(([rt, count]) => {
                                for (let i = 0; i < count; i++) {
                                    post.reactions.push({ reaction_type: rt });
                                }
                            });
                        }
                    }
                })
                .catch(() => {});
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
            }
        }
    }
</script>
@endsection
