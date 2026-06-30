@extends('layouts.frontend')

@section('title', 'İletişim - Yalıhan Emlak')

@section('content')
<div class="bg-white dark:bg-slate-900 min-h-screen">
    <!-- Page Header — Kurumsal Banner -->
    <section style="background:#0F2A5C; padding-top:7rem; padding-bottom:2.5rem;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;font-size:0.75rem;font-weight:500;color:rgba(255,255,255,0.55);">
                <a href="{{ route('home') }}" style="color:rgba(255,255,255,0.55);text-decoration:none;">Ana Sayfa</a>
                <span>›</span>
                <span style="color:#fff;">İletişim</span>
            </div>
            <h1 style="font-size:clamp(1.75rem,3vw,2.5rem);font-weight:800;color:#fff;margin-bottom:0.5rem;letter-spacing:-0.01em;">
                Bize Ulaşın
            </h1>
            <p style="font-size:0.95rem;color:rgba(255,255,255,0.65);max-width:40rem;">
                Hayalinizdeki gayrimenkulü bulmanız için uzman kadromuzla buradayız.
            </p>
        </div>
    </section>

    <!-- Contact Content Section -->
    <section class="py-12 md:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
            
            <!-- Contact Form -->
            <div class="bg-white dark:bg-slate-800 p-8 md:p-12 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-8">Mesaj Gönderin</h2>
                <form action="#" method="POST" id="contactForm" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">AD SOYAD</label>
                            <input name="name" class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-[var(--gold)]/50 focus:border-[var(--gold)] outline-none transition-all" placeholder="Adınız Soyadınız" type="text" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">E-POSTA</label>
                            <input name="email" class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-[var(--gold)]/50 focus:border-[var(--gold)] outline-none transition-all" placeholder="ornek@alanadi.com" type="email" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">TELEFON</label>
                            <input name="phone" class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-[var(--gold)]/50 focus:border-[var(--gold)] outline-none transition-all" placeholder="+90 5XX XXX XX XX" type="tel" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">KONU</label>
                            <select name="subject" class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-[var(--gold)]/50 focus:border-[var(--gold)] outline-none transition-all appearance-none" required>
                                <option value="">Seçiniz</option>
                                <option value="satis">Satılık İlanlar</option>
                                <option value="kiralama">Kiralık İlanlar</option>
                                <option value="yatirim">Yatırım Danışmanlığı</option>
                                <option value="diger">Diğer</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">MESAJINIZ</label>
                        <textarea name="message" class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-[var(--gold)]/50 focus:border-[var(--gold)] outline-none transition-all resize-none" placeholder="Size nasıl yardımcı olabiliriz?" rows="5" required></textarea>
                    </div>

                    <button class="w-full py-4 rounded-xl font-bold text-white transition-all transform hover:-translate-y-0.5 shadow-lg active:scale-95" style="background: var(--navy);" type="submit">
                        Mesajı Gönder
                    </button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="space-y-12 lg:pl-8">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-8">Kurumsal Bilgiler</h2>
                    
                    <div class="space-y-8">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: var(--gold-dim); color: var(--gold);">
                                <x-icon name="konum" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2 dark:text-white">Merkez Ofis (Bodrum)</h3>
                                <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
                                    Yalıkavak Marina, Çökertme Cd. No:14<br>
                                    48400 Bodrum / Muğla, Türkiye
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: var(--gold-dim); color: var(--gold);">
                                <x-icon name="telefon" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2 dark:text-white">Telefon</h3>
                                <div class="space-y-1">
                                    <p class="text-slate-600 dark:text-slate-300">+90 (252) 316 00 00</p>
                                    <a href="#" class="inline-flex items-center gap-2 font-bold hover:underline" style="color: var(--gold);">
                                        <x-icon name="gonder" class="w-4 h-4" /> WhatsApp Hattı
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: var(--gold-dim); color: var(--gold);">
                                <x-icon name="eposta" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2 dark:text-white">E-posta</h3>
                                <p class="text-slate-600 dark:text-slate-300">kurumsal@yalihanemlak.com</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800 p-8 rounded-2xl border border-slate-100 dark:border-slate-700">
                    <h3 class="text-xl font-bold mb-4 dark:text-white">Çalışma Saatleri</h3>
                    <div class="space-y-2 text-slate-600 dark:text-slate-300">
                        <div class="flex justify-between">
                            <span>Pazartesi - Cuma</span>
                            <span class="font-bold text-slate-900 dark:text-white">09:00 - 19:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Cumartesi</span>
                            <span class="font-bold text-slate-900 dark:text-white">10:00 - 17:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Pazar</span>
                            <span class="font-bold" style="color: var(--gold);">Randevu ile</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dark Themed Map Section -->
    <section class="w-full h-[500px] relative bg-slate-900 group overflow-hidden">
        <!-- CSS map grid pattern (no external image) -->
        <div class="absolute inset-0" style="background: linear-gradient(160deg, #0A1628 0%, #0d2044 50%, #0a2a5e 100%);">
            <svg class="absolute inset-0 w-full h-full opacity-10" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="map-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke="#C9A84C" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#map-grid)"/>
            </svg>
            <!-- Radial gold accent -->
            <div class="absolute inset-0" style="background: radial-gradient(ellipse 60% 50% at 50% 50%, rgba(201,168,76,0.08) 0%, transparent 70%);"></div>
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent"></div>
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="p-6 rounded-full shadow-[0_0_50px_rgba(201,168,76,0.5)] animate-pulse" style="background: var(--gold);">
                <x-icon name="konum" class="w-8 h-8 text-white" />
            </div>
            
            <div class="absolute translate-y-24 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-2xl pointer-events-auto border border-slate-100 dark:border-slate-700 text-center">
                <p class="font-bold" style="color: var(--navy);">Yalıhan Emlak Bodrum Office</p>
                <p class="text-sm text-slate-500">Yalıkavak Marina</p>
                <a class="mt-2 text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-[var(--gold)] flex items-center justify-center gap-1 transition-colors" href="https://maps.google.com" target="_blank">
                    Yol Tarifi Al &rarr;
                </a>
            </div>
        </div>
    </section>

    <!-- Global Offices Section -->
    <section class="py-24 bg-slate-50 dark:bg-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">Küresel Varlığımız</h2>
                <p class="text-slate-600 dark:text-slate-300 max-w-xl mx-auto">Dünyanın en prestijli lokasyonlarında, yerel uzmanlığımız ve küresel ağımızla yanınızdayız.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Office 1 — İstanbul -->
                <div class="group cursor-pointer">
                    <div class="aspect-[4/3] rounded-2xl overflow-hidden mb-6 relative">
                        <div class="w-full h-full transition-transform duration-500 group-hover:scale-110" style="background: linear-gradient(135deg, #0A1628 0%, #1a3a5c 40%, #0d2a4a 100%);">
                            <svg class="absolute inset-0 w-full h-full opacity-15" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid-ist" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="#C9A84C" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(#grid-ist)"/></svg>
                            <!-- Bosphorus Bridge silhouette -->
                            <div class="absolute inset-0 flex items-end justify-center pb-8 opacity-30">
                                <svg viewBox="0 0 200 80" xmlns="http://www.w3.org/2000/svg" class="w-48 h-24" fill="none">
                                    <path d="M0 60 Q50 20 100 30 Q150 20 200 60" stroke="#C9A84C" stroke-width="2"/>
                                    <line x1="70" y1="25" x2="70" y2="65" stroke="#C9A84C" stroke-width="1.5"/>
                                    <line x1="130" y1="25" x2="130" y2="65" stroke="#C9A84C" stroke-width="1.5"/>
                                    <rect x="65" y="15" width="10" height="12" fill="#C9A84C" rx="1"/>
                                    <rect x="125" y="15" width="10" height="12" fill="#C9A84C" rx="1"/>
                                    <line x1="0" y1="65" x2="200" y2="65" stroke="#C9A84C" stroke-width="1"/>
                                </svg>
                            </div>
                            <div class="absolute bottom-4 left-4">
                                <span class="text-xs font-bold tracking-widest uppercase" style="color: var(--gold);">Türkiye</span>
                            </div>
                        </div>
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: rgba(10, 22, 40, 0.3);"></div>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">İstanbul</h3>
                    <p class="text-slate-500 mt-2">Zorlu Center, No:2 Beşiktaş</p>
                </div>

                <!-- Office 2 — Londra -->
                <div class="group cursor-pointer">
                    <div class="aspect-[4/3] rounded-2xl overflow-hidden mb-6 relative">
                        <div class="w-full h-full transition-transform duration-500 group-hover:scale-110" style="background: linear-gradient(135deg, #1a2a1a 0%, #0d2a1a 40%, #0a1f14 100%);">
                            <svg class="absolute inset-0 w-full h-full opacity-15" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid-lon" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="#C9A84C" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(#grid-lon)"/></svg>
                            <!-- Big Ben silhouette -->
                            <div class="absolute inset-0 flex items-end justify-center pb-4 opacity-30">
                                <svg viewBox="0 0 60 100" xmlns="http://www.w3.org/2000/svg" class="w-16 h-28" fill="#C9A84C">
                                    <rect x="20" y="60" width="20" height="40" rx="1"/>
                                    <rect x="18" y="50" width="24" height="12" rx="1"/>
                                    <rect x="22" y="30" width="16" height="22" rx="1"/>
                                    <polygon points="30,5 22,28 38,28"/>
                                </svg>
                            </div>
                            <div class="absolute bottom-4 left-4">
                                <span class="text-xs font-bold tracking-widest uppercase" style="color: var(--gold);">İngiltere</span>
                            </div>
                        </div>
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: rgba(10, 22, 40, 0.3);"></div>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Londra</h3>
                    <p class="text-slate-500 mt-2">Berkeley Square, Mayfair W1J</p>
                </div>

                <!-- Office 3 — Dubai -->
                <div class="group cursor-pointer">
                    <div class="aspect-[4/3] rounded-2xl overflow-hidden mb-6 relative">
                        <div class="w-full h-full transition-transform duration-500 group-hover:scale-110" style="background: linear-gradient(135deg, #1a1000 0%, #2a1a00 40%, #1a0f00 100%);">
                            <svg class="absolute inset-0 w-full h-full opacity-15" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid-dxb" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="#C9A84C" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(#grid-dxb)"/></svg>
                            <!-- Burj Khalifa silhouette -->
                            <div class="absolute inset-0 flex items-end justify-center pb-4 opacity-30">
                                <svg viewBox="0 0 40 120" xmlns="http://www.w3.org/2000/svg" class="w-10 h-32" fill="#C9A84C">
                                    <rect x="14" y="80" width="12" height="40" rx="1"/>
                                    <rect x="13" y="65" width="14" height="17" rx="1"/>
                                    <rect x="15" y="50" width="10" height="17" rx="1"/>
                                    <rect x="16" y="35" width="8" height="17" rx="1"/>
                                    <rect x="17" y="20" width="6" height="17" rx="1"/>
                                    <rect x="18" y="5" width="4" height="17" rx="1"/>
                                    <line x1="20" y1="0" x2="20" y2="8" stroke="#C9A84C" stroke-width="2"/>
                                </svg>
                            </div>
                            <div class="absolute bottom-4 left-4">
                                <span class="text-xs font-bold tracking-widest uppercase" style="color: var(--gold);">BAE</span>
                            </div>
                        </div>
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: rgba(10, 22, 40, 0.3);"></div>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Dubai</h3>
                    <p class="text-slate-500 mt-2">Burj Daman, DIFC Level 15</p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    // Form Simulation (toast)
    document.getElementById('contactForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const oldText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '⏳ Gönderiliyor...';

        setTimeout(() => {
            btn.innerHTML = '✅ Gönderildi';
            btn.style.background = '#10B981'; // Green
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = oldText;
                btn.style.background = 'var(--navy)';
                this.reset();
                alert('Mesajınız başarıyla iletildi. En kısa sürede dönüş yapacağız.');
            }, 2000);
        }, 1500);
    });
</script>
@endpush
