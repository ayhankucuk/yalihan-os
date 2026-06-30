@props([
    'showNewsletter' => true,
    'showSocial' => true,
    'showContact' => true,
    'class' => '',
])

<footer class="yaliihan-footer {{ $class }} bg-gray-900 text-white">
    <!-- Newsletter Section -->
    @if ($showNewsletter)
        <div class="bg-orange-600 py-12">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-4xl text-center">
                    <h3 class="mb-4 text-3xl font-bold">📧 Haberlerden Haberdar Olun</h3>
                    <p class="mb-8 text-xl opacity-90">Yeni ilanlar ve özel fırsatlar hakkında bilgi alın</p>

                    <div class="mx-auto flex max-w-md flex-col gap-4 sm:flex-row">
                        <input type="email" placeholder="E-posta adresiniz"
                            class="flex-1 rounded-lg border-0 p-4 text-gray-900 focus:outline-none focus:ring-2 focus:ring-white dark:text-slate-100 dark:text-white">
                        <button
                            class="rounded-lg bg-white px-8 py-4 font-semibold text-orange-600 transition-colors hover:bg-gray-100 dark:bg-slate-900">
                            Abone Ol
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Footer Content -->
    <div class="py-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                <!-- Company Info -->
                <div class="lg:col-span-1">
                    <div class="mb-6">
                        <h3 class="mb-4 text-2xl font-bold text-orange-500">🏠 Yalıhan Emlak</h3>
                        <p class="leading-relaxed text-gray-300">
                            Bodrum'un en güvenilir emlak danışmanlık firması. 20+ yıllık deneyimimizle
                            hayalinizdeki evi bulmanızda yanınızdayız.
                        </p>
                    </div>

                    @if ($showContact)
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="text-orange-500">📍</span>
                                <span class="text-gray-300">Yalıkavak, Şeyhül İslam Ömer Lütfi Cd. No:10 D:C, 48400
                                    Bodrum/Muğla</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-orange-500">📞</span>
                                <a href="tel:+905332090302"
                                    class="text-gray-300 transition-colors hover:text-orange-500">
                                    0533 209 03 02
                                </a>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-orange-500">✉️</span>
                                <a href="mailto:info@yalihanemlak.com"
                                    class="text-gray-300 transition-colors hover:text-orange-500">
                                    info@yalihanemlak.com
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="mb-6 text-xl font-semibold">Hızlı Linkler</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Ana
                                Sayfa</a></li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Satılık
                                İlanlar</a></li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Kiralık
                                İlanlar</a></li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Villalar</a>
                        </li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Daireler</a>
                        </li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Arsalar</a>
                        </li>
                    </ul>
                </div>

                <!-- Services -->
                <div>
                    <h4 class="mb-6 text-xl font-semibold">Hizmetlerimiz</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Emlak
                                Danışmanlığı</a></li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Gayrimenkul
                                Değerleme</a></li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Hukuki
                                Danışmanlık</a></li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Finansman
                                Desteği</a></li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Sigorta
                                Hizmetleri</a></li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">360° Sanal
                                Tur</a></li>
                    </ul>
                </div>

                <!-- Locations -->
                <div>
                    <h4 class="mb-6 text-xl font-semibold">Popüler Bölgeler</h4>
                    <ul class="space-y-3">
                        <li><a href="#"
                                class="text-gray-300 transition-colors hover:text-orange-500">Yalıkavak</a></li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Gümbet</a>
                        </li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Bitez</a>
                        </li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Bodrum
                                Merkez</a></li>
                        <li><a href="#" class="text-gray-300 transition-colors hover:text-orange-500">Türkbükü</a>
                        </li>
                        <li><a href="#"
                                class="text-gray-300 transition-colors hover:text-orange-500">Göltürkbükü</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media & Bottom Bar -->
    <div class="border-t border-gray-800 py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                <!-- Social Media -->
                @if ($showSocial)
                    <div class="flex items-center gap-4">
                        <span class="text-gray-400">Bizi Takip Edin:</span>
                        <div class="flex gap-3">
                            <a href="https://www.facebook.com/yalihanemlak/" target="_blank"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-800 transition-colors hover:bg-orange-500"
                                title="Facebook">
                                📘
                            </a>
                            <a href="https://www.instagram.com/yalihanemlak/" target="_blank"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-800 transition-colors hover:bg-orange-500"
                                title="Instagram">
                                📷
                            </a>
                            <a href="https://twitter.com/yalihanemlak/" target="_blank"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-800 transition-colors hover:bg-orange-500"
                                title="Twitter">
                                🐦
                            </a>
                            <a href="https://wa.me/905332090302" target="_blank"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-800 transition-colors hover:bg-orange-500"
                                title="WhatsApp">
                                💬
                            </a>
                            <a href="https://t.me/ayhankucuk" target="_blank"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-800 transition-colors hover:bg-orange-500"
                                title="Telegram">
                                ✈️
                            </a>
                            <a href="https://vk.com/yalihanemlak" target="_blank"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-800 transition-colors hover:bg-orange-500"
                                title="VKontakte">
                                🔵
                            </a>
                            <a href="#"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-800 transition-colors hover:bg-orange-500"
                                title="YouTube">
                                📺
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Copyright -->
                <div class="text-center md:text-right">
                    <p class="text-sm text-gray-400">
                        © {{ date('Y') }} Yalıhan Emlak. Tüm hakları saklıdır.
                    </p>
                    <div class="mt-2 flex flex-wrap justify-center gap-4 text-xs text-gray-500 md:justify-end">
                        <a href="#" class="transition-colors hover:text-orange-500">Gizlilik Politikası</a>
                        <a href="#" class="transition-colors hover:text-orange-500">Kullanım Şartları</a>
                        <a href="#" class="transition-colors hover:text-orange-500">Çerez Politikası</a>
                        <a href="#" class="transition-colors hover:text-orange-500">KVKK</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="backToTop"
        class="invisible fixed bottom-6 right-6 z-50 h-12 w-12 rounded-full bg-orange-500 text-white opacity-0 shadow-lg transition-all duration-300 hover:bg-orange-600"
        onclick="scrollToTop()">
        ⬆️
    </button>
</footer>

<script>
    // Back to Top Button
    window.addEventListener('scroll', function() {
        const backToTop = document.getElementById('backToTop');
        if (window.pageYOffset > 300) {
            backToTop.classList.remove('opacity-0', 'invisible');
            backToTop.classList.add('opacity-100', 'visible');
        } else {
            backToTop.classList.add('opacity-0', 'invisible');
            backToTop.classList.remove('opacity-100', 'visible');
        }
    });

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Newsletter Subscription
    function subscribeNewsletter() {
        const email = document.querySelector('input[type="email"]').value;
        if (email) {
            showToast('Başarıyla abone oldunuz!', 'success');
            document.querySelector('input[type="email"]').value = '';
        } else {
            showToast('Lütfen geçerli bir e-posta adresi girin.', 'error');
        }
    }

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

<style>
    .yaliihan-footer {
        position: relative;
    }

    .yaliihan-footer a {
        transition: all 0.3s ease;
    }

    .yaliihan-footer button {
        transition: all 0.3s ease;
    }

    /* Smooth scroll behavior */
    html {
        scroll-behavior: smooth;
    }

    /* Newsletter form focus states */
    .yaliihan-footer input[type="email"]:focus {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* Social media hover effects */
    .yaliihan-footer .social-link {
        transform: translateY(0);
        transition: all 0.3s ease;
    }

    .yaliihan-footer .social-link:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
    }
</style>
