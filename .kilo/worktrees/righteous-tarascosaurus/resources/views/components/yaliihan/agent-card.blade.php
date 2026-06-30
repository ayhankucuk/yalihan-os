@props(['agent', 'isOwner' => false])

<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 p-6 sticky top-24 dark:shadow-none">
    <!-- Header: Profile Image & Name -->
    <div class="flex items-center space-x-4 mb-6">
        <div class="relative">
            <img src="{{ $agent->profile_photo_path ? \Illuminate\Support\Facades\Storage::url($agent->profile_photo_path) : asset('images/default-avatar.png') }}"
                 alt="{{ $agent->name ?? 'Danışman' }}"
                 class="w-16 h-16 rounded-full object-cover border-2 border-white dark:border-slate-800 shadow-md dark:shadow-none">
            @if(isset($agent->is_verified) && $agent->is_verified)
                <div class="absolute bottom-0 right-0 bg-blue-500 text-white p-1 rounded-full text-[10px] border-2 border-white dark:border-slate-800" title="Doğrulanmış Danışman">
                    <i class="fas fa-check"></i>
                </div>
            @endif
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white leading-tight dark:text-slate-100">
                {{ $agent->name ?? 'İsimsiz' }}
                {{ $agent->surname ?? '' }}
                @if($isOwner)
                    <span class="text-xs font-normal px-2 py-0.5 bg-green-100 text-green-700 rounded-full ml-1 dark:bg-green-900/30 dark:text-green-400">Mal Sahibi</span>
                @endif
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $agent->title ?? 'Emlak Danışmanı' }}</p>

            <!-- Rating (Mockup for now) -->
            <div class="flex items-center mt-1 text-xs">
                <div class="flex text-yellow-400">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <span class="ml-1 text-gray-400 dark:text-gray-500">(4.9)</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="space-y-3 mb-6">
        @if(!empty($agent->phone_number))
            <a href="tel:{{ $agent->phone_number }}"
               class="flex items-center justify-center w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all shadow-lg shadow-blue-200 dark:shadow-none hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-phone mr-2"></i> Ara
            </a>
        @endif

        <div class="flex gap-2">
            @if(!empty($agent->whatsapp_number))
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $agent->whatsapp_number) }}" target="_blank"
                   class="flex-1 flex items-center justify-center py-3 px-4 bg-green-500 hover:bg-green-600 text-white rounded-xl font-medium transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <i class="fab fa-whatsapp mr-2"></i> WhatsApp
                </a>
            @endif

            @if(!empty($agent->email))
                <a href="mailto:{{ $agent->email }}"
                   class="flex items-center justify-center w-12 py-3 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition-all dark:bg-slate-900" title="E-posta Gönder">
                    <i class="fas fa-envelope"></i>
                </a>
            @endif
        </div>
    </div>

    <!-- Lead Form (Simple) -->
    <div class="pt-6 border-t border-gray-100 dark:border-slate-800">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">Mesaj Gönder</h4>
        <form action="{{ route('frontend.forms.contact.submit') }}" method="POST" class="space-y-3">
            @csrf

            @if(isset($ilan_id))
                <input type="hidden" name="ilan_id" value="{{ $ilan_id }}">
            @endif

            <div>
                <label for="name" class="sr-only">Adınız</label>
                <input type="text" name="name" id="name" placeholder="Adınız Soyadınız" required
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white transition-all dark:border-slate-700">
            </div>

            <div>
                <label for="phone" class="sr-only">Telefon</label>
                <input type="tel" name="phone" id="phone" placeholder="Telefon Numaranız" required
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white transition-all dark:border-slate-700">
            </div>

            <div>
                <label for="message" class="sr-only">Mesajınız</label>
                <textarea name="message" id="message" rows="3" placeholder="Bu ilan hakkında bilgi almak istiyorum..."
                          class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white transition-all resize-none dark:border-slate-700"></textarea>
            </div>

            <button type="submit"
                    class="w-full py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
                Mesajı Gönder
            </button>

            <p class="text-xs text-center text-gray-400 dark:text-gray-500 mt-2">
                KVKK kapsamında verileriniz güvendedir.
            </p>
        </form>
    </div>
</div>
