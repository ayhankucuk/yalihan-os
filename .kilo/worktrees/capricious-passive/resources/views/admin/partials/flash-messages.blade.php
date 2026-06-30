{{-- Flash Messages Partial --}}
{{-- Bu partial tüm flash mesajlarını gösterir --}}
{{-- SAB Context7: FA yasak — x-icon bileşeni kullanılıyor --}}

@if(session()->has('success'))
    <div class="flash-message flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl mb-4 shadow-sm"
         role="alert" aria-live="polite">
        <x-icon name="onay-daire" class="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400" />
        <span class="text-sm font-medium">{{ session('success') }}</span>
        <button type="button" onclick="this.closest('.flash-message').remove()"
                class="ml-auto text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-200 transition-colors">
            <x-icon name="kapat" class="w-4 h-4" />
        </button>
    </div>
@endif

@if(session()->has('error'))
    <div class="flash-message flash-message-error flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 text-red-800 dark:text-red-300 px-4 py-3 rounded-xl mb-4 shadow-sm"
         role="alert" aria-live="assertive">
        <x-icon name="hata" class="w-5 h-5 flex-shrink-0 text-red-600 dark:text-red-400" />
        <span class="text-sm font-medium">{{ session('error') }}</span>
        <button type="button" onclick="this.closest('.flash-message').remove()"
                class="ml-auto text-red-500 hover:text-red-700 dark:hover:text-red-200 transition-colors">
            <x-icon name="kapat" class="w-4 h-4" />
        </button>
    </div>
@endif

@if(session()->has('warning'))
    <div class="flash-message flex items-center gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 text-amber-800 dark:text-amber-300 px-4 py-3 rounded-xl mb-4 shadow-sm"
         role="alert" aria-live="polite">
        <x-icon name="uyari" class="w-5 h-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
        <span class="text-sm font-medium">{{ session('warning') }}</span>
        <button type="button" onclick="this.closest('.flash-message').remove()"
                class="ml-auto text-amber-500 hover:text-amber-700 dark:hover:text-amber-200 transition-colors">
            <x-icon name="kapat" class="w-4 h-4" />
        </button>
    </div>
@endif

@if(session()->has('info'))
    <div class="flash-message flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/40 text-blue-800 dark:text-blue-300 px-4 py-3 rounded-xl mb-4 shadow-sm"
         role="alert" aria-live="polite">
        <x-icon name="bilgi" class="w-5 h-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
        <span class="text-sm font-medium">{{ session('info') }}</span>
        <button type="button" onclick="this.closest('.flash-message').remove()"
                class="ml-auto text-blue-500 hover:text-blue-700 dark:hover:text-blue-200 transition-colors">
            <x-icon name="kapat" class="w-4 h-4" />
        </button>
    </div>
@endif

{{-- Validation Errors --}}
@if($errors->any())
    <div class="flash-message flash-message-error flex items-start gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 text-red-800 dark:text-red-300 px-4 py-3 rounded-xl mb-4 shadow-sm"
         role="alert" aria-live="assertive">
        <x-icon name="hata" class="w-5 h-5 flex-shrink-0 text-red-600 dark:text-red-400 mt-0.5" />
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold mb-1">Lütfen aşağıdaki hataları düzeltin:</p>
            <ul class="text-sm list-disc list-inside space-y-0.5 opacity-90">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- Otomatik kapatma --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 5 saniye sonra success mesajlarını kapat
    setTimeout(function () {
        document.querySelectorAll('.flash-message:not(.flash-message-error)').forEach(function (el) {
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-4px)';
            setTimeout(function () { el.remove(); }, 400);
        });
    }, 5000);
});
</script>
