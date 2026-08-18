@extends('layouts.frontend')

@section('content')
{{-- Data Deletion Page — Meta App Publish Compliance --}}

<div class="min-h-screen bg-gradient-to-b from-slate-50 to-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">

        {{-- Header --}}
        <header class="text-center mb-12">
            <span class="inline-block px-4 py-1.5 bg-navy/5 text-navy text-sm font-medium rounded-full mb-4">
                Meta Uyumluluğu
            </span>
            <h1 class="text-4xl lg:text-5xl font-display font-bold text-navy mb-4">
                Veri Silme Talimatı
            </h1>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                Yalıhan Emlak hesabınızdaki kişisel verilerinizi nasıl sileceğinizi adım adım açıklıyoruz.
            </p>
            <p class="text-sm text-slate-500 mt-2">
                Son güncelleme: {{ now()->format('d F Y') }}
            </p>
        </header>

        {{-- Alert --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-8">
            <div class="flex gap-4">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-amber-800 mb-1">Önemli Bilgi</h3>
                    <p class="text-sm text-amber-700">
                        Veri silme talebiniz, Meta platformu üzerinden Facebook/Instagram ile bağlantılı verileri kapsamaz.
                        Meta verilerinizi silmek için doğrudan Meta'nın ilgili aracını kullanmanız gerekmektedir.
                    </p>
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            <div class="p-8 lg:p-12 space-y-8">

                {{-- Section 1: What data we collect --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </span>
                        1. Hangi Verileriniz Toplanır
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Yalıhan Emlak sistemlerinde aşağıdaki verileriniz saklanabilir:</p>
                        <ul class="list-disc pl-6 space-y-2 mt-4">
                            <li>Hesap bilgileri (ad, soyad, e-posta, telefon)</li>
                            <li>Gayrimenkul tercihleri ve arama kayıtları</li>
                            <li>Talep ve teklif geçmişi</li>
                            <li>İletişim kayıtları</li>
                            <li>İlan görüntüleme ve favorileme bilgileri</li>
                            <li>Çerez verileri (tercihler, oturum bilgileri)</li>
                        </ul>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 2: How to request deletion --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </span>
                        2. Veri Silme Talebi Nasıl Yapılır
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Verilerinizin silinmesini talep etmek için aşağıdaki yöntemlerden birini kullanabilirsiniz:</p>

                        {{-- Option 1 --}}
                        <div class="bg-slate-50 rounded-lg p-5 mt-6">
                            <h3 class="font-semibold text-navy mb-3 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-navy text-white text-sm flex items-center justify-center">1</span>
                                E-posta ile Talep
                            </h3>
                            <p class="text-sm text-slate-600 mb-3">
                                Aşağıdaki e-posta adresine talebinizi gönderebilirsiniz:
                            </p>
                            <div class="flex items-center gap-2">
                                <code class="bg-white px-3 py-2 rounded border border-slate-200 text-sm">
                                    kvkk@yalihanemlak.com.tr
                                </code>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">
                                E-posta konusu: "Veri Silme Talebi"
                            </p>
                        </div>

                        {{-- Option 2 --}}
                        <div class="bg-slate-50 rounded-lg p-5 mt-4">
                            <h3 class="font-semibold text-navy mb-3 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-navy text-white text-sm flex items-center justify-center">2</span>
                                Yazılı Başvuru
                            </h3>
                            <p class="text-sm text-slate-600">
                                Kimliğinizi teyit eden bir dilekçe ile Bodrum'daki ofisimize elden veya posta ile başvurabilirsiniz.
                            </p>
                            <div class="bg-white rounded border border-slate-200 p-3 mt-3">
                                <p class="text-sm text-slate-700">
                                    <strong>Adres:</strong> Bodrum, Muğla, Türkiye
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 3: What happens after deletion --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        3. Silme Süreci
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Silme talebinizi aldıktan sonra:</p>
                        <ul class="list-disc pl-6 space-y-2 mt-4">
                            <li><strong>Kimlik doğrulama:</strong> Kimliğinizi teyit etmek için sizinle iletişime geçebiliriz</li>
                            <li><strong>Değerlendirme:</strong> Silme talebiniz 30 gün içinde değerlendirilir</li>
                            <li><strong>Silme:</strong> Onaylanan talepler 30 gün içinde işlenir</li>
                            <li><strong>Yasal saklama:</strong> Yasal yükümlülükler kapsamında bazı veriler daha uzun süre saklanabilir</li>
                        </ul>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                            <p class="text-sm text-green-800">
                                <strong>Onay:</strong> Silme işlemi tamamlandığında size e-posta ile bilgi verilecektir.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 4: Data retention --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke="currentColor" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </span>
                        4. Saklanması Zorunlu Veriler
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Aşağıdaki durumlarda verileriniz yasal zorunluluk nedeniyle saklanabilir:</p>
                        <ul class="list-disc pl-6 space-y-2 mt-4">
                            <li>Vergi mevzuatı kapsamında ticari kayıtlar</li>
                            <li>Mahkeme kararları ve yasal süreçler</li>
                            <li>Suç soruşturmaları</li>
                            <li>Muafiyet süreleri (talep tarihinden itibaren 2 yıl)</li>
                        </ul>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 5: Contact --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        5. İletişim Bilgileri
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Veri silme talepleriniz için:</p>
                        <div class="bg-slate-50 rounded-lg p-4 mt-4 space-y-2">
                            <p><strong>KVKK Sorumlusu:</strong> Yalıhan Emlak</p>
                            <p><strong>E-posta:</strong> <a href="mailto:kvkk@yalihanemlak.com.tr" class="text-gold hover:underline">kvkk@yalihanemlak.com.tr</a></p>
                            <p><strong>Genel İletişim:</strong> <a href="mailto:info@yalihanemlak.com.tr" class="text-gold hover:underline">info@yalihanemlak.com.tr</a></p>
                            <p><strong>Adres:</strong> Bodrum, Muğla, Türkiye</p>
                        </div>
                    </div>
                </section>

            </div>
        </div>

        {{-- Meta Data Deletion Info --}}
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.341-3.369-1.341-.454-1.155-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z"/>
                </svg>
                Meta (Facebook/Instagram) Verileri Hakkında
            </h3>
            <p class="text-sm text-blue-800">
                Bu sayfa, Yalıhan Emlak sistemlerindeki verilerinizin silinmesi içindir. Meta platform verilerinizi
                (Facebook/Instagram ile paylaştığınız veriler) silmek için lütfen
                <a href="https://www.facebook.com/help/delete_account" target="_blank" rel="noopener" class="underline font-medium">
                    Facebook Veri Silme Aracı
                </a>
                veya
                <a href="https://www.instagram.com/accounts/remove/request/permanent/" target="_blank" rel="noopener" class="underline font-medium">
                    Instagram Hesap Silme
                </a>
                sayfalarını kullanın.
            </p>
        </div>

        {{-- Footer navigation --}}
        <div class="mt-8 flex justify-center gap-4">
            <a href="{{ route('legal.privacy') }}" class="text-slate-600 hover:text-navy text-sm font-medium">
                Gizlilik Politikası
            </a>
            <span class="text-slate-300">|</span>
            <a href="{{ route('legal.terms') }}" class="text-slate-600 hover:text-navy text-sm font-medium">
                Kullanım Şartları
            </a>
        </div>

    </div>
</div>
@endsection
