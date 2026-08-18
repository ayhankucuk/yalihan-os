@extends('layouts.frontend')

@section('content')
{{-- Privacy Policy Page — Meta App Publish Compliance --}}

<div class="min-h-screen bg-gradient-to-b from-slate-50 to-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">

        {{-- Header --}}
        <header class="text-center mb-12">
            <span class="inline-block px-4 py-1.5 bg-navy/5 text-navy text-sm font-medium rounded-full mb-4">
                Yasal Uyum
            </span>
            <h1 class="text-4xl lg:text-5xl font-display font-bold text-navy mb-4">
                Gizlilik Politikası
            </h1>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                Yalıhan Emlak olarak kişisel verilerinizin gizliliğini korumayı taahhüt ediyoruz.
            </p>
            <p class="text-sm text-slate-500 mt-2">
                Son güncelleme: {{ now()->format('d F Y') }}
            </p>
        </header>

        {{-- Content --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            <div class="p-8 lg:p-12 space-y-8">

                {{-- Section 1 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </span>
                        1. Veri Sorumlusu
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>
                            Bu web sitesi (<strong>yalihanemlak.com.tr</strong>) Yalıhan Emlak tarafından işletilmektedir.
                            Kişisel verileriniz, 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") kapsamında
                            veri sorumlusu sıfatıyla Yalıhan Emlak tarafından işlenmektedir.
                        </p>
                        <div class="bg-slate-50 rounded-lg p-4 mt-4">
                            <p class="font-medium text-navy">Yalıhan Emlak</p>
                            <p class="text-sm text-slate-600">Bodrum, Muğla, Türkiye</p>
                            <p class="text-sm text-slate-600">E-posta: info@yalihanemlak.com.tr</p>
                        </div>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 2 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </span>
                        2. Toplanan Kişisel Veriler
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Hizmetlerimizi sunabilmek için aşağıdaki kategorilerde kişisel veri toplayabiliriz:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Kimlik bilgileri:</strong> Ad, soyad, T.C. kimlik numarası</li>
                            <li><strong>İletişim bilgileri:</strong> Telefon numarası, e-posta adresi, adres</li>
                            <li><strong>Gayrimenkul tercihleri:</strong> Aranan bölge, bütçe, oda sayısı</li>
                            <li><strong>İşlem verileri:</strong> İlan görüntüleme, teklif ve talep kayıtları</li>
                            <li><strong>Teknik veriler:</strong> IP adresi, çerezler, tarayıcı bilgileri</li>
                        </ul>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 3 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        3. Verilerin İşlenme Amaçları
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Kişisel verileriniz aşağıdaki amaçlarla işlenmektedir:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Gayrimenkul danışmanlık hizmetlerinin sunulması</li>
                            <li>İlan ve teklif eşleştirmesi</li>
                            <li>Müşteri taleplerinin karşılanması</li>
                            <li>Yasal yükümlülüklerin yerine getirilmesi</li>
                            <li>Hizmet kalitesinin artırılması</li>
                        </ul>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 4 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </span>
                        4. Veri Aktarımı
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>
                            Kişisel verileriniz, yukarıda belirtilen amaçlar doğrultusunda ve KVKK'nın 8. ve 9. maddelerinde
                            öngörülen şartlar çerçevesinde; iş ortaklarımız, tedarikçilerimiz ve yasal zorunluluklar
                            kapsamında yetkili kurum ve kuruluşlarla paylaşılabilir.
                        </p>
                        <p class="mt-4">
                            Verileriniz, açık rızanız olmadan üçüncü ülkelere aktarılmamaktadır.
                        </p>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 5 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        5. Saklama Süresi
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>
                            Kişisel verileriniz, işlenme amaçlarının gerektirdiği süre boyunca saklanır.
                            Yasal yükümlülüklerimiz kapsamında daha uzun süre saklanması gereken veriler,
                            ilgili mevzuatta belirlenen süreler boyunca muhafaza edilir.
                        </p>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 6 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        6. Veri Sahibinin Hakları
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>KVKK'nın 11. maddesi uyarınca aşağıdaki haklara sahipsiniz:</p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme</li>
                            <li>İşlenmişse buna ilişkin bilgi talep etme</li>
                            <li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme</li>
                            <li>Yurt içinde veya yurt dışında aktarıldığı kişileri bilme</li>
                            <li>Eksik veya yanlış işlenmişse düzeltilmesini isteme</li>
                            <li>Silinmesini veya yok edilmesini isteme</li>
                            <li>Yapılan işlemlerin veri aktarılan kişilere bildirilmesini isteme</li>
                            <li>İşlenen verilerin aleyhine bir sonuç çıkmasına itiraz etme</li>
                        </ul>
                        <div class="bg-gold/5 border border-gold/20 rounded-lg p-4 mt-4">
                            <p class="text-sm text-slate-700">
                                <strong>Başvuru:</strong> Haklarınızı kullanmak için
                                <a href="mailto:kvkk@yalihanemlak.com.tr" class="text-gold hover:underline">kvkk@yalihanemlak.com.tr</a>
                                adresine e-posta gönderebilirsiniz.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 7 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </span>
                        7. Çerez Politikası
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>
                            Web sitemiz, kullanıcı deneyimini iyileştirmek ve hizmetlerimizi sunmak için çerezler kullanmaktadır.
                            Çerezler, tarayıcınız tarafından cihazınıza kaydedilen küçük metin dosyalarıdır.
                        </p>
                        <p class="mt-4">
                            <strong>Kullandığımız çerez türleri:</strong>
                        </p>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><strong>Zorunlu çerezler:</strong> Site işlevselliği için gerekli</li>
                            <li><strong>Analitik çerezler:</strong> Site kullanımını anlamamıza yardımcı olur</li>
                            <li><strong>Tercih çerezleri:</strong> Dil ve para birimi tercihlerinizi hatırlar</li>
                        </ul>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 8 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        8. İletişim
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Gizlilik politikamız hakkında sorularınız için bizimle iletişime geçebilirsiniz:</p>
                        <div class="bg-slate-50 rounded-lg p-4 mt-4 space-y-2">
                            <p><strong>E-posta:</strong> <a href="mailto:info@yalihanemlak.com.tr" class="text-gold hover:underline">info@yalihanemlak.com.tr</a></p>
                            <p><strong>KVKK başvurusu:</strong> <a href="mailto:kvkk@yalihanemlak.com.tr" class="text-gold hover:underline">kvkk@yalihanemlak.com.tr</a></p>
                            <p><strong>Adres:</strong> Bodrum, Muğla, Türkiye</p>
                        </div>
                    </div>
                </section>

            </div>
        </div>

        {{-- Footer navigation --}}
        <div class="mt-8 flex justify-center gap-4">
            <a href="{{ route('legal.terms') }}" class="text-slate-600 hover:text-navy text-sm font-medium">
                Kullanım Şartları
            </a>
            <span class="text-slate-300">|</span>
            <a href="{{ route('legal.data-deletion') }}" class="text-slate-600 hover:text-navy text-sm font-medium">
                Veri Silme Talimatı
            </a>
        </div>

    </div>
</div>
@endsection
