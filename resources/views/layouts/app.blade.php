<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect - Web</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <!-- Laravel Echo + Pusher for Reverb -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://unpkg.com/laravel-echo@2.3.7/dist/echo.iife.js"></script>
    <script>
        window.Pusher = Pusher;
        try {
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: '{{ config("broadcasting.connections.reverb.key") }}',
                wsHost: '{{ config("broadcasting.connections.reverb.options.host") ?? "127.0.0.1" }}',
                wsPort: '{{ config("broadcasting.connections.reverb.options.port") ?? 8080 }}',
                wssPort: '{{ config("broadcasting.connections.reverb.options.port") ?? 8080 }}',
                forceTLS: false,
                enabledTransports: ['ws', 'wss'],
            });
        } catch(e) {
            console.warn('Echo/Reverb not available:', e.message);
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('Service Worker registered:', reg.scope))
                .catch(err => console.warn('Service Worker registration failed:', err));
        }

        function requestNotificationPermission() {
            if (!('Notification' in window)) {
                console.warn('Notifications not supported');
                return Promise.resolve('denied');
            }
            if (Notification.permission === 'granted') {
                return Promise.resolve('granted');
            }
            if (Notification.permission === 'denied') {
                return Promise.resolve('denied');
            }
            return Notification.requestPermission();
        }

        function showBrowserNotification(notification) {
            if (Notification.permission !== 'granted') return;
            if (document.visibilityState === 'visible') return;

            const notif = new Notification(notification.title || 'Notifikasi Baru', {
                body: notification.body || '',
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                vibrate: [200, 100, 200],
                tag: notification.id ? `notification-${notification.id}` : 'notification',
                data: notification.data || {},
            });

            notif.onclick = () => {
                window.focus();
                if (notification.data && notification.data.url) {
                    window.location.href = notification.data.url;
                }
                notif.close();
            };
        }

        @auth
        if (window.Echo) {
            const userId = {{ Auth::id() }};
            window.Echo.private(`user.${userId}`)
                .notification((notification) => {
                    console.log('Notification received:', notification);
                    showBrowserNotification(notification);
                    if (window.Alpine) {
                        Alpine.store('notifications', {
                            unreadCount: (Alpine.store('notifications')?.unreadCount ?? 0) + 1,
                        });
                    }
                });
        }
        @endauth

        document.addEventListener('alpine:init', () => {
            Alpine.store('notifications', {
                unreadCount: 0,
                async fetchUnreadCount() {
                    try {
                        const res = await fetch('/api/notifications/unread-count');
                        if (res.ok) {
                            const data = await res.json();
                            this.unreadCount = data.count ?? 0;
                        }
                    } catch (e) {
                        console.warn('Failed to fetch unread count:', e);
                    }
                },
            });
        });
    </script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            mint: '#A8E6CF',
                            peach: '#FFD3B6',
                            lilac: '#DCD3FF',
                            blue: '#BDE0FE',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .clay {
            border-radius: 20px;
            border: 3px solid rgba(0,0,0,0.04);
            box-shadow: inset -2px -2px 8px rgba(0,0,0,0.04), 4px 4px 12px rgba(0,0,0,0.06);
        }
        .clay-sm {
            border-radius: 16px;
            border: 2px solid rgba(0,0,0,0.04);
            box-shadow: inset -1px -1px 4px rgba(0,0,0,0.04), 2px 2px 8px rgba(0,0,0,0.06);
        }
        .clay-input {
            border-radius: 16px;
            border: 2px solid rgba(0,0,0,0.06);
            box-shadow: inset 2px 2px 6px rgba(0,0,0,0.06), inset -1px -1px 4px rgba(255,255,255,0.8);
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .clay-input:focus {
            border-color: #BDE0FE;
            box-shadow: inset 2px 2px 6px rgba(0,0,0,0.06), inset -1px -1px 4px rgba(255,255,255,0.8), 0 0 0 3px rgba(189,224,254,0.3);
            outline: none;
        }
        .clay-btn {
            border-radius: 20px;
            border: 3px solid rgba(0,0,0,0.04);
            box-shadow: inset -1px -1px 4px rgba(0,0,0,0.04), 3px 3px 8px rgba(0,0,0,0.08);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .clay-btn:hover {
            transform: translateY(-1px);
            box-shadow: inset -1px -1px 4px rgba(0,0,0,0.04), 5px 5px 12px rgba(0,0,0,0.1);
        }
        .clay-btn:active {
            transform: translateY(1px);
            box-shadow: inset 1px 1px 4px rgba(0,0,0,0.08), 1px 1px 4px rgba(0,0,0,0.04);
        }
        .clay-bubble-self {
            border-radius: 20px 20px 4px 20px;
            border: 2px solid rgba(0,0,0,0.04);
            box-shadow: inset -1px -1px 4px rgba(0,0,0,0.04), 2px 2px 8px rgba(0,0,0,0.06);
        }
        .clay-bubble-other {
            border-radius: 20px 20px 20px 4px;
            border: 2px solid rgba(0,0,0,0.04);
            box-shadow: inset -1px -1px 4px rgba(0,0,0,0.04), 2px 2px 8px rgba(0,0,0,0.06);
        }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-slide-up {
            animation: fadeSlideUp 0.3s ease-out;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">
    
    <nav class="sticky top-0 z-50" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); box-shadow: 0 8px 32px rgba(0,0,0,0.04), 0 2px 8px rgba(0,0,0,0.02);">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14 items-center">
                <div class="flex items-center space-x-2">
                    <a href="{{ route('home') }}" class="clay-sm w-9 h-9 flex items-center justify-center bg-gradient-to-br from-brand-mint to-brand-blue hover:shadow-lg transition-all duration-200">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </a>
                    @auth
                        <a href="{{ route('chat.index') }}" class="clay-sm w-9 h-9 flex items-center justify-center transition-all duration-200 {{ request()->routeIs('chat.*') ? 'bg-brand-peach bg-opacity-40' : 'bg-white hover:bg-brand-peach hover:bg-opacity-30' }}">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </a>
                        <a href="{{ route('forums.index') }}" class="clay-sm w-9 h-9 flex items-center justify-center transition-all duration-200 {{ request()->routeIs('forums.*') ? 'bg-brand-lilac bg-opacity-40' : 'bg-white hover:bg-brand-lilac hover:bg-opacity-30' }}">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                        </a>
                    @endauth
                </div>
                <div class="flex items-center space-x-2">
                    @auth
                        <div x-data x-init="$store.notifications.fetchUnreadCount()" class="relative">
                            <button @click="requestPermission(); $store.notifications.fetchUnreadCount(); window.location.href='/notifications'"
                                class="clay-sm w-9 h-9 flex items-center justify-center bg-white hover:bg-brand-blue hover:bg-opacity-30 transition-all duration-200 relative"
                                :class="{ 'bg-brand-blue bg-opacity-20': $store.notifications.unreadCount > 0 }"
                                title="Notifikasi">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.035-.585 1.416L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span x-show="$store.notifications.unreadCount > 0"
                                    class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center animate-pulse"
                                    x-text="$store.notifications.unreadCount > 9 ? '9+' : $store.notifications.unreadCount"></span>
                            </button>
                            <script>
                                function requestPermission() {
                                    if ('Notification' in window && Notification.permission === 'default') {
                                        Notification.requestPermission();
                                    }
                                }
                            </script>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="clay-sm w-9 h-9 flex items-center justify-center bg-white hover:bg-brand-lilac hover:bg-opacity-30 transition-all duration-200">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold" style="background: linear-gradient(135deg, #A8E6CF, #BDE0FE);">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="clay-sm w-9 h-9 flex items-center justify-center bg-white hover:bg-brand-peach hover:bg-opacity-30 transition-all duration-200">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        </a>
                        <a href="{{ route('register') }}" class="clay-sm w-9 h-9 flex items-center justify-center" style="background: linear-gradient(135deg, #BDE0FE, #DCD3FF);">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 min-h-[calc(100vh-3.5rem-4rem)]">
        @yield('content')
    </main>

    <footer class="text-center py-6 text-xs text-gray-400 border-t-2 border-gray-100">
        STMIK WIDYA UTAMA &copy; 2026
    </footer>

</body>
</html>
