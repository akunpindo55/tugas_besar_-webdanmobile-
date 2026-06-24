<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect - Jaringan Sosial Mahasiswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
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
                            cream: '#FFF5E4',
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
        .clay-btn {
            border-radius: 20px;
            border: 3px solid rgba(0,0,0,0.04);
            box-shadow: inset -1px -1px 4px rgba(0,0,0,0.04), 3px 3px 8px rgba(0,0,0,0.08);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .clay-btn:hover {
            transform: translateY(-2px);
            box-shadow: inset -1px -1px 4px rgba(0,0,0,0.04), 6px 6px 16px rgba(0,0,0,0.12);
        }
        .clay-btn:active {
            transform: translateY(1px);
            box-shadow: inset 1px 1px 4px rgba(0,0,0,0.08), 1px 1px 4px rgba(0,0,0,0.04);
        }
        .clay-card {
            border-radius: 24px;
            border: 3px solid rgba(0,0,0,0.04);
            box-shadow: inset -3px -3px 10px rgba(0,0,0,0.04), 6px 6px 20px rgba(0,0,0,0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .clay-card:hover {
            transform: translateY(-4px);
            box-shadow: inset -3px -3px 10px rgba(0,0,0,0.04), 10px 10px 30px rgba(0,0,0,0.12);
        }
        .cursor::after {
            content: '|';
            animation: blink 0.7s step-end infinite;
            margin-left: 2px;
        }
        @keyframes blink {
            50% { opacity: 0; }
        }
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body class="bg-[#F5F0EB] text-gray-800 font-sans antialiased overflow-x-hidden">

    <!-- Navbar -->
    <nav class="sticky top-0 z-50" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); box-shadow: 0 8px 32px rgba(0,0,0,0.04), 0 2px 8px rgba(0,0,0,0.02);">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14 items-center">
                <div class="flex items-center space-x-2">
                    <a href="{{ route('landing') }}" class="clay-sm w-9 h-9 flex items-center justify-center bg-gradient-to-br from-brand-mint to-brand-blue hover:shadow-lg transition-all duration-200">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </a>
                </div>
                <div class="flex items-center">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ route('home') }}" class="clay-sm w-9 h-9 flex items-center justify-center" style="background: linear-gradient(135deg, #BDE0FE, #DCD3FF);">
                                <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="clay-sm w-9 h-9 flex items-center justify-center bg-white hover:bg-brand-peach hover:bg-opacity-30 transition-all duration-200">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            </a>
                            <a href="{{ route('register') }}" class="clay-sm w-9 h-9 flex items-center justify-center" style="background: linear-gradient(135deg, #BDE0FE, #DCD3FF);">
                                <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 lg:pt-28 lg:pb-20">
        <div class="text-center">
            <div class="inline-block clay-sm bg-brand-cream px-5 py-2 mb-6">
                <span class="text-sm font-bold text-gray-600">🌱 Jaringan Sosial Khusus Mahasiswa</span>
            </div>
            <h1 class="text-4xl lg:text-6xl font-extrabold text-gray-900 leading-tight mb-6">
                <span class="cursor text-5xl lg:text-7xl" id="typing-text"></span>
            </h1>
            <p class="text-lg lg:text-xl text-gray-500 max-w-2xl mx-auto mb-10 leading-relaxed">
                Campus Connect adalah platform sosial yang menghubungkan mahasiswa dari berbagai kampus. 
                Berbagi cerita, berdiskusi dalam forum, dan terhubung dengan teman baru.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                @auth
                    <a href="{{ route('home') }}" class="clay-btn bg-brand-blue font-bold py-3 px-8 text-lg">Mulai Jelajahi</a>
                @else
                    <a href="{{ route('register') }}" class="clay-btn bg-brand-peach font-bold py-3 px-8 text-lg">Mulai Sekarang</a>
                    <a href="{{ route('login') }}" class="text-gray-500 font-medium hover:text-gray-700">Sudah punya akun? <span class="underline">Masuk</span></a>
                @endauth
            </div>
        </div>
    </section>

    <!-- Brand color blocks decoration -->
    <div class="flex justify-center gap-3 pb-16">
        <div class="w-16 h-2 rounded-full bg-brand-mint"></div>
        <div class="w-16 h-2 rounded-full bg-brand-peach"></div>
        <div class="w-16 h-2 rounded-full bg-brand-lilac"></div>
        <div class="w-16 h-2 rounded-full bg-brand-blue"></div>
    </div>

    <!-- Features Section -->
    <section id="features" class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
        <div class="text-center mb-14">
            <h2 class="text-3xl lg:text-4xl font-extrabold text-gray-900 mb-4">Fitur Lengkap untuk Kehidupan Kampus</h2>
            <p class="text-gray-500 text-lg">Semua yang kamu butuhkan untuk terhubung dan berkembang</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Card 1: Feed -->
            <div class="clay-card p-8 bg-white card-gsap">
                <div class="w-14 h-14 rounded-2xl bg-brand-mint bg-opacity-40 flex items-center justify-center text-2xl mb-5 clay-sm">📝</div>
                <h3 class="text-xl font-bold mb-3">Feed Kampus</h3>
                <p class="text-gray-500 leading-relaxed">Bagikan momen, berita, dan pengalaman kampusmu. Berinteraksi dengan sesama mahasiswa melalui postingan publik.</p>
            </div>

            <!-- Card 2: Chat -->
            <div class="clay-card p-8 bg-white card-gsap">
                <div class="w-14 h-14 rounded-2xl bg-brand-peach bg-opacity-40 flex items-center justify-center text-2xl mb-5 clay-sm">💬</div>
                <h3 class="text-xl font-bold mb-3">Obrolan Real-time</h3>
                <p class="text-gray-500 leading-relaxed">Chat langsung dengan teman kampusmu. Buat obrolan grup, kirim gambar, dan tetap terhubung secara real-time.</p>
            </div>

            <!-- Card 3: Forum -->
            <div class="clay-card p-8 bg-white card-gsap">
                <div class="w-14 h-14 rounded-2xl bg-brand-lilac bg-opacity-40 flex items-center justify-center text-2xl mb-5 clay-sm">📚</div>
                <h3 class="text-xl font-bold mb-3">Forum Diskusi</h3>
                <p class="text-gray-500 leading-relaxed">Buat forum diskusi untuk mata kuliah, organisasi, atau topik favorit. Bahas tugas, proyek, dan ide bersama.</p>
            </div>

            <!-- Card 4: Profile -->
            <div class="clay-card p-8 bg-white card-gsap">
                <div class="w-14 h-14 rounded-2xl bg-brand-blue bg-opacity-40 flex items-center justify-center text-2xl mb-5 clay-sm">👤</div>
                <h3 class="text-xl font-bold mb-3">Profil Kustom</h3>
                <p class="text-gray-500 leading-relaxed">Tampilkan identitas kampusmu dengan profil yang bisa disesuaikan.</p>
            </div>

            <!-- Card 5: Community -->
            <div class="clay-card p-8 bg-white card-gsap">
                <div class="w-14 h-14 rounded-2xl bg-brand-peach bg-opacity-40 flex items-center justify-center text-2xl mb-5 clay-sm">🤝</div>
                <h3 class="text-xl font-bold mb-3">Komunitas Kampus</h3>
                <p class="text-gray-500 leading-relaxed">Temukan dan bergabung dengan komunitas sesuai minatmu. Perluas jaringan dan kenali teman dari berbagai jurusan.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-white py-20 clay-sm rounded-none -mx-0">
        <div class="max-w-3xl mx-auto text-center px-4 sm:px-6">
            <div class="clay-card p-10 lg:p-14 bg-brand-cream">
                <h2 class="text-3xl lg:text-4xl font-extrabold text-gray-900 mb-4">Siap Bergabung?</h2>
                <p class="text-gray-500 text-lg mb-8">Ribuan mahasiswa sudah terhubung. Jadilah bagian dari komunitas kampus terbesar!</p>
                @auth
                    <a href="{{ route('home') }}" class="clay-btn bg-brand-blue font-bold py-3 px-10 text-lg">Ke Dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="clay-btn bg-brand-peach font-bold py-3 px-10 text-lg">Daftar Gratis</a>
                @endauth
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white/50 py-10 text-center text-gray-400 text-sm">
        <div class="max-w-5xl mx-auto px-4">
            <div class="flex items-center justify-center space-x-2 mb-4">
                <span class="text-xl">🎓</span>
                <span class="font-bold text-gray-600">Campus Connect</span>
            </div>
            <p>&copy; 2026 STMIK WIDYA UTAMA. Platform sosial mahasiswa.</p>
        </div>
    </footer>

    <!-- GSAP Animations -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Typing text effect
            const phrases = [
                'Temukan Teman Baru',
                'Bagikan Cerita Kampus',
                'Bahas Tugas Kuliah',
                'Eksplor Minat & Bakat',
                'Bangun Jaringanmu'
            ];
            const el = document.getElementById('typing-text');
            let phraseIndex = 0;
            let charIndex = 0;
            let isDeleting = false;
            let isPaused = false;

            function typeEffect() {
                if (isPaused) {
                    setTimeout(typeEffect, 2000);
                    isPaused = false;
                    return;
                }

                const current = phrases[phraseIndex];
                if (!isDeleting) {
                    el.textContent = current.substring(0, charIndex + 1);
                    charIndex++;
                    if (charIndex === current.length) {
                        isPaused = true;
                        isDeleting = true;
                        setTimeout(typeEffect, 2500);
                        return;
                    }
                    setTimeout(typeEffect, 70 + Math.random() * 40);
                } else {
                    el.textContent = current.substring(0, charIndex - 1);
                    charIndex--;
                    if (charIndex === 0) {
                        isDeleting = false;
                        phraseIndex = (phraseIndex + 1) % phrases.length;
                        setTimeout(typeEffect, 400);
                        return;
                    }
                    setTimeout(typeEffect, 30 + Math.random() * 20);
                }
            }
            typeEffect();

            // GSAP card animations
            gsap.registerPlugin(ScrollTrigger);

            gsap.from('.card-gsap', {
                y: 50,
                opacity: 0,
                duration: 0.7,
                stagger: 0.15,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: '#features',
                    start: 'top 75%',
                }
            });

            gsap.from('.hero-content > *', {
                y: 30,
                opacity: 0,
                duration: 0.6,
                stagger: 0.1,
                ease: 'power2.out',
                delay: 0.2
            });
        });
    </script>

</body>
</html>
