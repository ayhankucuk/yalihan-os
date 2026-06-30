<!-- Session Başarı Mesajı -->
@if (session('success'))
<div class="bg-green-100 dark:bg-green-900/50 border-l-4 border-green-500 dark:border-green-400 text-green-700 dark:text-green-300 p-4 mb-6 rounded shadow-sm animate-fadeIn dark:shadow-none" role="alert">
    <div class="flex items-center">
        <div class="py-1">
            <svg class="fill-current h-5 w-5 text-green-500 dark:text-green-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path>
            </svg>
        </div>
        <div>
            <p class="font-semibold">{{ session('success') }}</p>
        </div>
    </div>
</div>
@endif

<!-- Session Hata Mesajı -->
@if (session('error'))
<div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-500 dark:border-red-400 text-red-700 dark:text-red-300 p-4 mb-6 rounded shadow-sm animate-fadeIn dark:shadow-none" role="alert">
    <div class="flex items-center">
        <div class="py-1">
            <svg class="fill-current h-5 w-5 text-red-500 dark:text-red-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"></path>
            </svg>
        </div>
        <div>
            <p class="font-semibold">{{ session('error') }}</p>
        </div>
    </div>
</div>
@endif

<!-- Form Hataları -->
@if ($errors->any())
<div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-500 dark:border-red-400 text-red-700 dark:text-red-300 p-4 mb-6 rounded shadow-sm animate-fadeIn dark:shadow-none" role="alert">
    <div class="flex">
        <div class="py-1">
            <svg class="fill-current h-5 w-5 text-red-500 dark:text-red-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"></path>
            </svg>
        </div>
        <div>
            <p class="font-semibold">Lütfen aşağıdaki hataları düzeltin:</p>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

<!-- Uyarı Mesajı -->
@if (session('warning'))
<div class="bg-yellow-100 dark:bg-yellow-900/50 border-l-4 border-yellow-500 dark:border-yellow-400 text-yellow-700 dark:text-yellow-300 p-4 mb-6 rounded shadow-sm animate-fadeIn dark:shadow-none" role="alert">
    <div class="flex items-center">
        <div class="py-1">
            <svg class="fill-current h-5 w-5 text-yellow-500 dark:text-yellow-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 7a1 1 0 012 0v4a1 1 0 11-2 0V7zm1 8a1 1 0 100-2 1 1 0 000 2z"></path>
            </svg>
        </div>
        <div>
            <p class="font-semibold">{{ session('warning') }}</p>
        </div>
    </div>
</div>
@endif



<!-- Session Bilgi Mesajı -->
@if (session('info'))
<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded" role="alert">
    <div class="flex items-center">
        <div class="py-1">
            <svg class="fill-current h-5 w-5 text-blue-500 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-8a1 1 0 011 1v3a1 1 0 01-2 0v-3a1 1 0 011-1zm0-4a1 1 0 100 2 1 1 0 000-2z"></path>
            </svg>
        </div>
        <div>
            <p class="font-semibold">{{ session('info') }}</p>
        </div>
    </div>
</div>
@endif
