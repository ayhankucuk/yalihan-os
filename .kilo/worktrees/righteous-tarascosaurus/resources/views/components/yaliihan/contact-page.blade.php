@props([
    'showMap' => true,
    'showForm' => true,
    'showOfficeInfo' => true,
    'class' => '',
])

<div class="contact-page {{ $class }} min-h-screen bg-slate-50 dark:bg-gray-950 dark:bg-slate-900">
    <!-- Hero Section -->
    <x-yaliihan.hero-section title="📞 İletişim"
        subtitle="Bizimle iletişime geçin, size yardımcı olmaktan mutluluk duyarız" :show-search="false" />

    <!-- AI Assistant CTA -->
    <div class="container mx-auto -mt-10 px-4 sm:-mt-14">
        <div
            class="relative flex flex-col items-start gap-6 overflow-hidden rounded-3xl border border-blue-100 bg-white p-6 shadow-xl transition-all duration-300 dark:border-blue-900/40 dark:bg-slate-900 sm:p-8 lg:flex-row">
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-br from-blue-50 via-transparent to-purple-50 dark:from-blue-900/40 dark:via-transparent dark:to-purple-900/30">
            </div>
            <div class="relative flex items-center gap-5">
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-purple-600 text-white shadow-lg sm:h-20 sm:w-20">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 11c1.657 0 3-1.567 3-3.5S13.657 4 12 4 9 5.567 9 7.5 10.343 11 12 11zM9.5 13a4.5 4.5 0 00-4.5 4.5V19a1 1 0 001 1h10a1 1 0 001-1v-1.5A4.5 4.5 0 0012.5 13h-3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 7h3m-1.5-1.5V9" />
                    </svg>
                </div>
                <div>
                    <p
                        class="mb-2 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-300">
                        <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-blue-500"></span>
                        Yapay Zeka Destekli Yardım
                    </p>
                    <h2 class="mb-1 text-xl font-bold text-gray-900 dark:text-slate-100 dark:text-white sm:text-2xl">
                        Sanal Danışmanımız 7/24 Hizmetinizde</h2>
                    <p class="text-sm text-gray-600 dark:text-slate-200">Sorularınıza anında yanıt alın, portföy
                        önerileri oluşturun ve iletişim sürecinizi hızlandırın.</p>
                </div>
            </div>
            <div class="relative ml-auto flex flex-col items-center gap-3 sm:flex-row">
                <a href="{{ url('/ai/explore') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white transition-all duration-200 hover:bg-blue-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-95 dark:focus:ring-offset-gray-900">
                    Sanal Danışmanla Sohbet Et
                    <i class="fas fa-arrow-right text-sm"></i>
                </a>
                <a href="tel:+905332090302"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 px-5 py-3 font-semibold text-blue-600 transition-all duration-200 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-blue-700 dark:text-blue-300 dark:hover:bg-blue-900/20 dark:focus:ring-offset-gray-900">
                    📞 0533 209 03 02
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-16">
        <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
            <!-- Contact Form -->
            @if ($showForm)
                <div
                    class="rounded-3xl border border-gray-200 bg-white p-8 shadow-xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900 sm:p-10">
                    <h2 class="mb-3 text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">Mesaj Gönderin
                    </h2>
                    <p class="mb-8 text-gray-600 dark:text-gray-400">Sorularınız, önerileriniz veya işbirliği
                        talepleriniz için formu doldurun. Ekibimiz en kısa sürede size dönüş yapar.</p>

                    <form method="POST" action="#" class="space-y-6" novalidate>
                        @csrf
                        <div class="hidden">
                            <label for="contact_hp">Lütfen bu alanı boş bırakın</label>
                            <input id="contact_hp" name="contact_hp" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label for="name"
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Ad
                                    Soyad *</label>
                                <input id="name" name="name" type="text" required autocomplete="name"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-500 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white dark:placeholder-gray-400"
                                    placeholder="Adınız ve soyadınız">
                            </div>

                            <div>
                                <label for="email"
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">E-posta
                                    *</label>
                                <input id="email" name="email" type="email" required autocomplete="email"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-500 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white dark:placeholder-gray-400"
                                    placeholder="E-posta adresiniz">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label for="phone"
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Telefon</label>
                                <input id="phone" name="phone" type="tel" autocomplete="tel"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-500 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white dark:placeholder-gray-400"
                                    placeholder="Telefon numaranız">
                            </div>

                            <div>
                                <label for="topic"
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Konu</label>
                                <select id="topic" name="topic" style="color-scheme: light dark;"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                    <option value="">Konu seçiniz</option>
                                    <option value="general">Genel Bilgi</option>
                                    <option value="property">Emlak Danışmanlığı</option>
                                    <option value="valuation">Değerleme Hizmeti</option>
                                    <option value="legal">Hukuki Danışmanlık</option>
                                    <option value="finance">Finansman Desteği</option>
                                    <option value="other">Diğer</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="message"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Mesaj
                                *</label>
                            <textarea id="message" name="message" rows="6" required
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-500 transition-all duration-200 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white dark:placeholder-gray-400"
                                placeholder="Mesajınızı detaylı bir şekilde yazın..."></textarea>
                        </div>

                        <div class="flex items-start gap-3">
                            <input type="checkbox" id="privacy" required
                                class="mt-1 h-5 w-5 rounded border-gray-300 bg-white text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                            <label for="privacy" class="text-sm text-gray-600 dark:text-gray-400">Gizlilik metnini
                                okudum ve kabul ediyorum. <a href="#"
                                    class="font-semibold text-blue-600 hover:underline dark:text-blue-300">Gizlilik
                                    Politikası</a></label>
                        </div>

                        <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 font-semibold text-white transition-all duration-200 hover:bg-blue-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-95 dark:focus:ring-offset-gray-900">
                            <i class="fas fa-paper-plane"></i>
                            Mesaj Gönder
                        </button>
                    </form>
                </div>
            @endif

            <!-- Office Info & Map -->
            <div class="space-y-8">
                @if ($showOfficeInfo)
                    <!-- Office Information -->
                    <div
                        class="rounded-3xl border border-gray-200 bg-white p-8 shadow-xl transition-all duration-300 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="mb-6 text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">Ofis
                            Bilgileri</h2>

                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                                    📍
                                </div>
                                <div>
                                    <h3 class="mb-1 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                        Adres</h3>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        Yalıkavak, Şeyhül İslam Ömer Lütfi Cd.<br>
                                        No:10 D:C, 48400 Bodrum/Muğla
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                                    📞
                                </div>
                                <div>
                                    <h3 class="mb-1 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                        Telefon</h3>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        <a href="tel:+905332090302"
                                            class="transition-colors hover:text-blue-600 dark:hover:text-blue-300">
                                            0533 209 03 02
                                        </a>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                                    ✉️
                                </div>
                                <div>
                                    <h3 class="mb-1 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                        E-posta</h3>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        <a href="mailto:info@yalihanemlak.com"
                                            class="transition-colors hover:text-blue-600 dark:hover:text-blue-300">
                                            info@yalihanemlak.com
                                        </a>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                                    🕒
                                </div>
                                <div>
                                    <h3 class="mb-1 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                        Çalışma Saatleri</h3>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        Pazartesi - Cuma: 09:00 - 18:00<br>
                                        Cumartesi: 09:00 - 16:00<br>
                                        Pazar: Kapalı
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($showMap)
                    <!-- Map -->
                    <div
                        class="rounded-3xl border border-gray-200 bg-white p-8 shadow-xl transition-all duration-300 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="mb-6 text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">Konum
                        </h2>
                        <x-yaliihan.map-component :center="[
                            'lat' => 37.0581,
                            'lng' => 27.258,
                        ]" :zoom="15" :markers="[
                            [
                                'position' => [
                                    'lat' => 37.0581,
                                    'lng' => 27.258,
                                ],
                                'title' => 'Yalıhan Emlak',
                                'content' => 'Yalıkavak, Şeyhül İslam Ömer Lütfi Cd. No:10 D:C, 48400 Bodrum/Muğla',
                                'icon' => null,
                            ],
                        ]" height="400px"
                            :show-traffic="true" :show-transit="true" :show-bicycling="false" class="contact-map" />
                    </div>
                @endif
            </div>
        </div>

        <!-- Team Section -->
        <div class="mt-16">
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">Ekibimiz</h2>
                <p class="text-gray-600 dark:text-gray-400">Deneyimli ve profesyonel ekibimizle tanışın</p>
            </div>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                <!-- Team Member 1 -->
                <div
                    class="team-member rounded-3xl border border-gray-200 bg-white p-6 text-center shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face"
                        alt="Ahmet Yılmaz" class="mx-auto mb-4 h-24 w-24 rounded-full object-cover">
                    <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Ahmet
                        Yılmaz</h3>
                    <p class="mb-2 font-medium text-blue-600 dark:text-blue-300">Genel Müdür</p>
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">20+ yıllık emlak deneyimi</p>
                    <div class="flex justify-center gap-2">
                        <a href="tel:+905332090302"
                            class="rounded-full bg-blue-100 p-2 text-blue-600 transition-colors hover:bg-blue-600 hover:text-white dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-700">
                            📞
                        </a>
                        <a href="mailto:ahmet@yalihanemlak.com"
                            class="rounded-full bg-blue-100 p-2 text-blue-600 transition-colors hover:bg-blue-600 hover:text-white dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-700">
                            ✉️
                        </a>
                    </div>
                </div>

                <!-- Team Member 2 -->
                <div
                    class="team-member rounded-3xl border border-gray-200 bg-white p-6 text-center shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=150&h=150&fit=crop&crop=face"
                        alt="Ayşe Demir" class="mx-auto mb-4 h-24 w-24 rounded-full object-cover">
                    <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Ayşe Demir
                    </h3>
                    <p class="mb-2 font-medium text-blue-600 dark:text-blue-300">Emlak Danışmanı</p>
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">15+ yıllık deneyim</p>
                    <div class="flex justify-center gap-2">
                        <a href="tel:+905332090303"
                            class="rounded-full bg-blue-100 p-2 text-blue-600 transition-colors hover:bg-blue-600 hover:text-white dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-700">
                            📞
                        </a>
                        <a href="mailto:ayse@yalihanemlak.com"
                            class="rounded-full bg-blue-100 p-2 text-blue-600 transition-colors hover:bg-blue-600 hover:text-white dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-700">
                            ✉️
                        </a>
                    </div>
                </div>

                <!-- Team Member 3 -->
                <div
                    class="team-member rounded-3xl border border-gray-200 bg-white p-6 text-center shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face"
                        alt="Mehmet Kaya" class="mx-auto mb-4 h-24 w-24 rounded-full object-cover">
                    <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Mehmet
                        Kaya</h3>
                    <p class="mb-2 font-medium text-blue-600 dark:text-blue-300">Hukuk Danışmanı</p>
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">10+ yıllık hukuk deneyimi</p>
                    <div class="flex justify-center gap-2">
                        <a href="tel:+905332090304"
                            class="rounded-full bg-blue-100 p-2 text-blue-600 transition-colors hover:bg-blue-600 hover:text-white dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-700">
                            📞
                        </a>
                        <a href="mailto:mehmet@yalihanemlak.com"
                            class="rounded-full bg-blue-100 p-2 text-blue-600 transition-colors hover:bg-blue-600 hover:text-white dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-700">
                            ✉️
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="mt-16">
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">Sıkça Sorulan
                    Sorular</h2>
                <p class="text-gray-600 dark:text-gray-400">Merak ettiğiniz konular hakkında bilgi alın</p>
            </div>

            <div class="mx-auto max-w-3xl">
                <div class="space-y-4">
                    <!-- FAQ Item 1 -->
                    <div
                        class="rounded-3xl border border-gray-200 bg-white shadow-xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900">
                        <button
                            class="flex w-full items-center justify-between p-6 text-left transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/20"
                            onclick="toggleFAQ(1)">
                            <span class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Emlak
                                danışmanlık hizmeti ücreti nedir?</span>
                            <span class="text-xl text-blue-600 dark:text-blue-300" id="faq-icon-1">+</span>
                        </button>
                        <div id="faq-content-1" class="hidden px-6 pb-6">
                            <p class="text-gray-600 dark:text-gray-400">Emlak danışmanlık hizmetimiz genellikle satış
                                bedelinin %2-3'
                                oranında ücretlendirilir. Detaylı bilgi için bizimle iletişime geçebilirsiniz.</p>
                        </div>
                    </div>

                    <!-- FAQ Item 2 -->
                    <div
                        class="rounded-3xl border border-gray-200 bg-white shadow-xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900">
                        <button
                            class="flex w-full items-center justify-between p-6 text-left transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/20"
                            onclick="toggleFAQ(2)">
                            <span class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Emlak
                                değerleme raporu ne kadar sürer?</span>
                            <span class="text-xl text-blue-600 dark:text-blue-300" id="faq-icon-2">+</span>
                        </button>
                        <div id="faq-content-2" class="hidden px-6 pb-6">
                            <p class="text-gray-600 dark:text-gray-400">Emlak değerleme raporu genellikle 3-5 iş günü
                                içinde hazırlanır.
                                Acil statuslar için hızlı değerleme hizmeti de sunmaktayız.</p>
                        </div>
                    </div>

                    <!-- FAQ Item 3 -->
                    <div
                        class="rounded-3xl border border-gray-200 bg-white shadow-xl transition-all duration-300 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <button
                            class="flex w-full items-center justify-between p-6 text-left transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/20"
                            onclick="toggleFAQ(3)">
                            <span class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Hangi
                                bölgelerde hizmet veriyorsunuz?</span>
                            <span class="text-xl text-blue-600 dark:text-blue-300" id="faq-icon-3">+</span>
                        </button>
                        <div id="faq-content-3" class="hidden px-6 pb-6">
                            <p class="text-gray-600 dark:text-gray-400">Bodrum'un tüm bölgelerinde hizmet vermekteyiz.
                                Yalıkavak, Gümbet,
                                Bitez, Bodrum Merkez, Türkbükü, Göltürkbükü ve çevre bölgelerde aktif olarak
                                çalışmaktayız.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // FAQ Toggle Function
    function toggleFAQ(id) {
        const content = document.getElementById(`faq-content-${id}`);
        const icon = document.getElementById(`faq-icon-${id}`);

        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.textContent = '-';
        } else {
            content.classList.add('hidden');
            icon.textContent = '+';
        }
    }

    // Form Submission
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                showToast('Mesajınız başarıyla gönderildi!', 'success');
                form.reset();
            });
        }
    });

    // Toast Notification
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 bg-white dark:bg-slate-800 rounded-lg p-4 shadow-lg border-l-4 ${
        type === 'success' ? 'border-green-500' : 'border-red-500'
    } z-50 transform translate-x-full transition-transform duration-300`;
        toast.innerHTML = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.remove('translate-x-full'), 100);
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 3000);
    }
</script>
