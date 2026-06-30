@extends('layouts.frontend')

@section('title', 'İletişim - Yalıhan Emlak')

@section('content')
<div class="bg-white dark:bg-slate-900 min-h-screen">
    <!-- Hero Section -->
    <section class="relative py-24 md:py-32 overflow-hidden bg-slate-50 dark:bg-slate-800">
        <div class="absolute inset-0 z-0 bg-gradient-to-b from-[var(--navy)]/5 to-transparent"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <span class="inline-block text-xs font-bold text-[var(--gold)] mb-4 tracking-widest uppercase">İLETİŞİM</span>
            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 dark:text-white mb-6">Bize Ulaşın</h1>
            <p class="max-w-2xl mx-auto text-lg text-slate-600 dark:text-slate-300">
                Hayalinizdeki gayrimenkulü bulmanız için uzman kadromuzla buradayız. 
                Yalıhan Emlak ayrıcalıklı dünyasına adım atmak için bize mesaj gönderin.
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
                                <x-icon name="location-marker" class="w-6 h-6" />
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
                                <x-icon name="phone" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2 dark:text-white">Telefon</h3>
                                <div class="space-y-1">
                                    <p class="text-slate-600 dark:text-slate-300">+90 (252) 316 00 00</p>
                                    <a href="#" class="inline-flex items-center gap-2 font-bold hover:underline" style="color: var(--gold);">
                                        <x-icon name="chat" class="w-4 h-4" /> WhatsApp Hattı
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: var(--gold-dim); color: var(--gold);">
                                <x-icon name="mail" class="w-6 h-6" />
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
    <section class="w-full h-[500px] relative bg-slate-900 group">
        <div class="absolute inset-0 bg-cover bg-center opacity-70 filter grayscale brightness-50 transition-all duration-700 group-hover:scale-105" 
             style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuBdsOGciXJy7oFWG0xhf47fzaJ9CZEio767rEqbhPvuqPIRU9gvfjkAYM1Bt5-DtAkMO_NN2Adljk10juG0EJ3cQRwa9EFCMWok2bbQUf3jhVmdPKQtHs90yVJaIIDeHfWxq8lyfcSK1NzyAlypbHnsKgyqOEMVVUHbE9kCiTcI0QSA-2n6bJNtEJWJC5MbSv_Gat_y-dp7fB1Rk0ZxIhXfDMewR4JSEe-18IBXVBSCR4DVoJcQmzekP4tpcqsRzyEPgiyEbXvRQQ')">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-slate-900/50"></div>
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="p-6 rounded-full shadow-[0_0_50px_rgba(201,168,76,0.5)] animate-pulse" style="background: var(--gold);">
                <x-icon name="location-marker" class="w-8 h-8 text-white" />
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
                <!-- Office 1 -->
                <div class="group cursor-pointer">
                    <div class="aspect-[4/3] rounded-2xl overflow-hidden mb-6 relative">
                        <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB2mat-Sc2W1mHwhl0yhJ8dq9QCQLWyEvrqtcqCLg4aEY4oqaAiIaX8DWeyVcP3FevKLqCL88UCCBDEbTaHDzgV-Jt7ZkHRD-V2rPD-DQflNcfmR0AzVm_jAS4QC8oWpfLWg65TopYeAbLyFyb61wctAywsD-6X7s0ct447jV8kI9utciaY0RBjHqsbPrfbUWdDsCzh7b7W5r-qgLrNrAiHhJC2vuUyC6q0hK6N8bBrhoPmU9Y7JQAfVZlUYelTC8z62TcYYXakRw">
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: rgba(10, 22, 40, 0.3);"></div>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">İstanbul</h3>
                    <p class="text-slate-500 mt-2">Zorlu Center, No:2 Beşiktaş</p>
                </div>

                <!-- Office 2 -->
                <div class="group cursor-pointer">
                    <div class="aspect-[4/3] rounded-2xl overflow-hidden mb-6 relative">
                        <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD64KZp6TAItv9cOmIP7Cb2Znv0BSTNYm2uie5C0wuHkFjJyCRWHmkgKwcJUh59F9DzcVibR8d1kIWoThxT0Ox4BgcLPfkxg6ORRCIpHV9A1lZ13qHAj2dr8pbAzQt7iT2chKARcvOUsS9ex8rHFg8KiI-ps6t6Yx6MJLZARXgGu7qYw7O3R3gtRw3yOZU6yWCvgtxFM0bHkpuiDuIw1A3fo8aOTrSw-j2DE6De7zIeIjvt65DLQEj1sB0AQueCadco3W2KlKR0gA">
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: rgba(10, 22, 40, 0.3);"></div>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Londra</h3>
                    <p class="text-slate-500 mt-2">Berkeley Square, Mayfair W1J</p>
                </div>

                <!-- Office 3 -->
                <div class="group cursor-pointer">
                    <div class="aspect-[4/3] rounded-2xl overflow-hidden mb-6 relative">
                        <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCbTUaluvdAQ6yMNKEFEER2MVnZkgcaH1Oe8j1BlqI95Niafx-BxJ-EWd4HjyhQjrtkyGNqci2Zb81hdgH99IT4_wH-Nkcp3Rg7W7o82-47ntZeEztP9I_Fs-YnXKELR8dUfTYEzvunSWkJFllyvzipwoFMGVyzWv7rNPBd_CQp2zoo5WK8aqpy0vC-QDpyhAwmeZYlwq-KsQ7U4Eh3YUjITQZB8Xuc_r16dV_NcjqJI5odfswY6SjmTIRVy0cQvZHFD6hkxMaDGQ">
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
