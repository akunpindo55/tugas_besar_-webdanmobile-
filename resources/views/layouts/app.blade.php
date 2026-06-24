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
    
    <nav class="bg-white border-b-2 border-gray-100 sticky top-0 z-50 clay-sm rounded-none">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-6">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900 tracking-tight">🎓 Campus Connect</a>
                    </div>
                    @auth
                        <a href="{{ route('chat.index') }}" class="text-sm font-medium text-gray-600 hover:text-brand-peach transition px-3 py-1.5 rounded-xl hover:bg-brand-peach hover:bg-opacity-20">Chat</a>
                    @endauth
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <span class="text-sm font-medium bg-brand-lilac bg-opacity-40 px-3 py-1 rounded-full clay-sm">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium clay-sm px-3 py-1 hover:bg-red-50 transition">Keluar</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Masuk</a>
                        <a href="{{ route('register') }}" class="text-sm font-medium bg-brand-blue px-4 py-2 rounded-xl hover:opacity-90 clay-btn">Daftar</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

</body>
</html>
