@props([
    'title' => '🏠 Yalıhan Emlak',
    'subtitle' => 'Bodrum\'un en güzel emlakları burada!',
    'showSearch' => true,
    'backgroundImage' => null,
    'overlay' => true,
    'locations' => [],
    'propertyTypes' => [],
])

<section
    class="hero-section relative {{ $backgroundImage ? 'bg-cover bg-center' : 'bg-gradient-to-br from-blue-600 via-purple-600 to-blue-800 dark:from-blue-900 dark:via-purple-900 dark:to-blue-950' }} py-16 md:py-24 text-white overflow-hidden transition-all duration-300">
    @if ($backgroundImage)
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $backgroundImage }}')"></div>
    @endif

    @if ($overlay)
        <div class="absolute inset-0 bg-black bg-opacity-40 dark:bg-opacity-60 transition-opacity duration-300"></div>
    @endif

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center max-w-4xl mx-auto">
            <!-- Main Title -->
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight transition-all duration-300">
                {{ $title }}
            </h1>

            <!-- Subtitle -->
            <p class="text-xl md:text-2xl mb-8 opacity-90 dark:opacity-95 transition-opacity duration-300">
                {{ $subtitle }}
            </p>

            @if ($showSearch)
                <div class="mt-10">
                    <x-yaliihan.hero-search-tabs :locations="$locations" :property-types="$propertyTypes" />
                </div>
            @endif

            <!-- Stats -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center transform hover:scale-105 transition-transform duration-300">
                    <div class="text-4xl font-bold mb-2">500+</div>
                    <div class="text-lg opacity-90 dark:opacity-95">Aktif İlan</div>
                </div>
                <div class="text-center transform hover:scale-105 transition-transform duration-300">
                    <div class="text-4xl font-bold mb-2">20+</div>
                    <div class="text-lg opacity-90 dark:opacity-95">Yıl Deneyim</div>
                </div>
                <div class="text-center transform hover:scale-105 transition-transform duration-300">
                    <div class="text-4xl font-bold mb-2">1000+</div>
                    <div class="text-lg opacity-90 dark:opacity-95">Mutlu Müşteri</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Elements -->
    <div class="absolute top-20 left-10 w-20 h-20 bg-white dark:bg-gray-500 bg-opacity-10 dark:bg-opacity-20 rounded-full animate-pulse transition-opacity duration-300 dark:bg-slate-900"></div>
    <div class="absolute bottom-20 right-10 w-16 h-16 bg-white dark:bg-gray-500 bg-opacity-10 dark:bg-opacity-20 rounded-full animate-pulse delay-1000 transition-opacity duration-300 dark:bg-slate-900"></div>
    <div class="absolute top-1/2 left-20 w-12 h-12 bg-white dark:bg-gray-500 bg-opacity-10 dark:bg-opacity-20 rounded-full animate-pulse delay-500 transition-opacity duration-300 dark:bg-slate-900"></div>
</section>

@push('styles')
<style>
    .hero-section {
        position: relative;
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-20px);
        }
    }

    .animate-float {
        animation: float 6s ease-in-out infinite;
    }

    .animate-float:nth-child(2) {
        animation-delay: 2s;
    }

    .animate-float:nth-child(3) {
        animation-delay: 4s;
    }
</style>
@endpush
