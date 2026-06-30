@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8">
    <!-- 👤 Temel Bilgiler -->
    <x-neo.card variant="primary" class="p-6">
        <h2 class="text-xl font-bold text-blue-800 mb-6 flex items-center">
            <i class="fas fa-user mr-3 text-blue-600"></i>
            👤 Temel Bilgiler
        </h2>

        <div class="space-y-4">
            <div class="flex justify-between items-center py-3 border-b border-blue-100">
                <span class="text-sm font-medium text-blue-700">Ad Soyad</span>
                <span class="text-sm text-blue-900 font-semibold">{{ $danisman->name }}</span>
            </div>

            <div class="flex justify-between items-center py-3 border-b border-blue-100">
                <span class="text-sm font-medium text-blue-700">E-posta</span>
                <span class="text-sm text-blue-900">{{ $danisman->email }}</span>
            </div>

            <div class="flex justify-between items-center py-3 border-b border-blue-100">
                <span class="text-sm font-medium text-blue-700">Telefon</span>
                <span class="text-sm text-blue-900">{{ $danisman->phone_number ?? 'Belirtilmemiş' }}</span>
            </div>

            @if($danisman->whatsapp_number)
            <div class="flex justify-between items-center py-3 border-b border-blue-100">
                <span class="text-sm font-medium text-blue-700">WhatsApp</span>
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $danisman->whatsapp_number) }}" target="_blank" class="text-sm text-blue-900 hover:text-blue-600">
                    {{ $danisman->whatsapp_number }}
                    <i class="fab fa-whatsapp ml-1"></i>
                </a>
            </div>
            @endif

            <div class="flex justify-between items-center py-3 border-b border-blue-100">
                <span class="text-sm font-medium text-blue-700">Ünvan</span>
                <span class="text-sm text-blue-900">{{ $danisman->title ?? 'Danışman' }}</span>
            </div>

            @if($danisman->position)
            <div class="flex justify-between items-center py-3 border-b border-blue-100">
                <span class="text-sm font-medium text-blue-700">Pozisyon</span>
                <span class="text-sm text-blue-900">{{ config('danisman.positions.' . $danisman->position, $danisman->position) }}</span>
            </div>
            @endif

            @if($danisman->department)
            <div class="flex justify-between items-center py-3 border-b border-blue-100">
                <span class="text-sm font-medium text-blue-700">Departman</span>
                <span class="text-sm text-blue-900">{{ config('danisman.departments.' . $danisman->department, $danisman->department) }}</span>
            </div>
            @endif

            <div class="flex justify-between items-center py-3 border-b border-blue-100">
                <span class="text-sm font-medium text-blue-700">Deneyim Yılı</span>
                <span class="text-sm text-blue-900">{{ $danisman->deneyim_yili ?? 0 }} yıl</span>
            </div>

            <div class="flex justify-between items-center py-3 border-b border-blue-100">
                <span class="text-sm font-medium text-blue-700">Lisans No</span>
                <span class="text-sm text-blue-900">{{ $danisman->lisans_no ?? 'Belirtilmemiş' }}</span>
            </div>

            <div class="flex justify-between items-center py-3">
                <span class="text-sm font-medium text-blue-700">Durum</span>
                @php
                    // aktiflik_durumu boolean kontrolü
                    $statusValue = $danisman->aktiflik_durumu ? 'aktif' : 'pasif';
                @endphp
                <x-neo.status-badge :value="$statusValue" />
            </div>
        </div>
    </x-neo.card>

    <!-- 📝 Hakkımda / Bio -->
    <x-neo.card variant="success" class="p-6">
        <h2 class="text-xl font-bold text-green-800 mb-6 flex items-center">
            <i class="fas fa-info-circle mr-3 text-green-600"></i>
            📝 Hakkımda
        </h2>

        <div class="space-y-4">
            @if($danisman->bio)
                <div class="prose max-w-none">
                    <p class="text-sm text-gray-700 dark:text-slate-200 leading-relaxed whitespace-pre-line dark:text-slate-300">{{ $danisman->bio }}</p>
                </div>
            @else
                <p class="text-sm text-gray-500 italic">Hakkımda bilgisi eklenmemiş.</p>
            @endif

            @if($danisman->expertise_summary)
                <div class="mt-4">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Uzmanlık Özeti</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $danisman->expertise_summary }}</p>
                </div>
            @endif

            @if($danisman->certificates_info)
                <div class="mt-4">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Sertifikalar</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed whitespace-pre-line">{{ $danisman->certificates_info }}</p>
                </div>
            @endif
        </div>
    </x-neo.card>

    <!-- 🌐 Sosyal Medya -->
    @if($danisman->instagram_profile || $danisman->linkedin_profile || $danisman->facebook_profile || $danisman->twitter_profile || $danisman->youtube_channel || $danisman->tiktok_profile || $danisman->website)
    <x-neo.card variant="purple" class="p-6">
        <h2 class="text-xl font-bold text-purple-800 mb-6 flex items-center">
            <i class="fas fa-share-alt mr-3 text-purple-600"></i>
            🌐 Sosyal Medya
        </h2>

        <div class="grid grid-cols-2 gap-4">
            @if($danisman->instagram_profile)
            <a href="{{ $danisman->instagram_profile }}" target="_blank" class="flex items-center gap-2 p-3 rounded-lg bg-gradient-to-r from-pink-500 to-purple-600 text-white hover:shadow-lg transition-all duration-200">
                <i class="fab fa-instagram text-xl"></i>
                <span class="text-sm font-medium">Instagram</span>
            </a>
            @endif

            @if($danisman->linkedin_profile)
            <a href="{{ $danisman->linkedin_profile }}" target="_blank" class="flex items-center gap-2 p-3 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:shadow-lg transition-all duration-200">
                <i class="fab fa-linkedin text-xl"></i>
                <span class="text-sm font-medium">LinkedIn</span>
            </a>
            @endif

            @if($danisman->facebook_profile)
            <a href="{{ $danisman->facebook_profile }}" target="_blank" class="flex items-center gap-2 p-3 rounded-lg bg-gradient-to-r from-blue-700 to-blue-800 text-white hover:shadow-lg transition-all duration-200">
                <i class="fab fa-facebook text-xl"></i>
                <span class="text-sm font-medium">Facebook</span>
            </a>
            @endif

            @if($danisman->twitter_profile)
            <a href="{{ $danisman->twitter_profile }}" target="_blank" class="flex items-center gap-2 p-3 rounded-lg bg-gradient-to-r from-blue-400 to-blue-500 text-white hover:shadow-lg transition-all duration-200">
                <i class="fab fa-twitter text-xl"></i>
                <span class="text-sm font-medium">Twitter</span>
            </a>
            @endif

            @if($danisman->youtube_channel)
            <a href="{{ $danisman->youtube_channel }}" target="_blank" class="flex items-center gap-2 p-3 rounded-lg bg-gradient-to-r from-red-600 to-red-700 text-white hover:shadow-lg transition-all duration-200">
                <i class="fab fa-youtube text-xl"></i>
                <span class="text-sm font-medium">YouTube</span>
            </a>
            @endif

            @if($danisman->tiktok_profile)
            <a href="{{ $danisman->tiktok_profile }}" target="_blank" class="flex items-center gap-2 p-3 rounded-lg bg-gradient-to-r from-gray-900 to-black text-white hover:shadow-lg transition-all duration-200">
                <i class="fab fa-tiktok text-xl"></i>
                <span class="text-sm font-medium">TikTok</span>
            </a>
            @endif

            @if($danisman->website)
            <a href="{{ $danisman->website }}" target="_blank" class="flex items-center gap-2 p-3 rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:shadow-lg transition-all duration-200 col-span-2">
                <i class="fas fa-globe text-xl"></i>
                <span class="text-sm font-medium">Website</span>
            </a>
            @endif
        </div>
    </x-neo.card>
    @endif

    <!-- ⭐ Uzmanlık Alanları -->
    <x-neo.card variant="warning" class="p-6">
        <h2 class="text-xl font-bold text-yellow-800 mb-6 flex items-center">
            <i class="fas fa-star mr-3 text-yellow-600"></i>
            ⭐ Uzmanlık Alanları
        </h2>

        <div class="space-y-6">
            @php
                // Backward compatibility: Eğer uzmanlik_alanlari yoksa ama uzmanlik_alani varsa onu göster
                $uzmanlikAlanlari = $danisman->uzmanlik_alanlari ?? [];
                if (empty($uzmanlikAlanlari) && $danisman->uzmanlik_alani) {
                    $uzmanlikAlanlari = [$danisman->uzmanlik_alani];
                }
            @endphp
            @if(count($uzmanlikAlanlari) > 0)
            <div>
                <h4 class="text-sm font-medium text-yellow-700 mb-3">Uzmanlık Alanları</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach ($uzmanlikAlanlari as $alan)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-700">
                            {{ $alan }}
                        </span>
                    @endforeach
                </div>
            </div>
            @else
            <div>
                <p class="text-sm text-yellow-600 dark:text-yellow-400">Uzmanlık alanı belirtilmemiş</p>
            </div>
            @endif

            @if($danisman->bolge_uzmanliklari && count($danisman->bolge_uzmanliklari) > 0)
            <div>
                <h4 class="text-sm font-medium text-yellow-700 mb-3">Bölge Uzmanlıkları</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach ($danisman->bolge_uzmanliklari as $bolge)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-700">
                            {{ $bolge }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($danisman->diller && count($danisman->diller) > 0)
            <div>
                <h4 class="text-sm font-medium text-yellow-700 mb-3">Diller</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach ($danisman->diller as $dil)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-700">
                            {{ $dil }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </x-neo.card>

    <!-- 🕒 İletişim ve Çalışma Bilgileri -->
    <x-neo.card variant="success" class="p-6">
        <h2 class="text-xl font-bold text-green-800 mb-6 flex items-center">
            <i class="fas fa-clock mr-3 text-green-600"></i>
            🕒 İletişim ve Çalışma Bilgileri
        </h2>

        <div class="space-y-6">
            @if($danisman->office_address)
            <div>
                <h4 class="text-sm font-medium text-green-700 mb-2">Ofis Adresi</h4>
                <p class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $danisman->office_address }}</p>
            </div>
            @endif

            @if($danisman->office_phone)
            <div>
                <h4 class="text-sm font-medium text-green-700 mb-2">Ofis Telefonu</h4>
                <p class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $danisman->office_phone }}</p>
            </div>
            @endif

            @if($danisman->iletisim_tercihleri && count($danisman->iletisim_tercihleri) > 0)
            <div>
                <h4 class="text-sm font-medium text-green-700 mb-3">İletişim Tercihleri</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach ($danisman->iletisim_tercihleri as $tercih)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-700">
                            {{ ucfirst($tercih) }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($danisman->calisma_saatleri && count($danisman->calisma_saatleri) > 0)
            <div>
                <h4 class="text-sm font-medium text-green-700 mb-3">Çalışma Saatleri</h4>
                <div class="space-y-1">
                    @foreach ($danisman->calisma_saatleri as $saat)
                        <p class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $saat }}</p>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </x-neo.card>
</div>

