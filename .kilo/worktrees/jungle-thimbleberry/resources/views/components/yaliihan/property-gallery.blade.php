@props(['images' => [], 'title' => 'Emlak Görseli'])

<div class="space-y-4">
    <!-- Desktop Grid Gallery (hidden on mobile) -->
    <div class="hidden md:grid grid-cols-4 grid-rows-2 gap-2 h-[400px] lg:h-[500px] rounded-2xl overflow-hidden relative group">
        <!-- Main Large Image (Left, spans 2 rows, 2 cols) -->
        <div class="col-span-2 row-span-2 relative overflow-hidden cursor-pointer" onclick="openLightbox(0)">
            @if(count($images) > 0)
                <img src="{{ $images[0] }}" alt="{{ $title }}" class="w-full h-full object-cover transition-transform duration-700 hover:scale-105">
            @else
                <div class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                    <span class="text-gray-400 text-4xl">📷</span>
                </div>
            @endif
        </div>

        <!-- Side Images (Right, 2x2 grid) -->
        @foreach(array_slice($images, 1, 4) as $index => $image)
            <div class="relative overflow-hidden cursor-pointer" onclick="openLightbox({{ $index + 1 }})">
                <img src="{{ $image }}" alt="{{ $title }} - {{ $index + 2 }}" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">

                <!-- Overlay for the last image to show 'View All' -->
                @if($index === 3 && count($images) > 5)
                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center group-hover:bg-black/40 transition-colors">
                        <span class="text-white font-bold text-lg">+{{ count($images) - 5 }} Fotoğraf</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Mobile Carousel (visible on mobile) -->
    <div class="md:hidden relative h-72 -mx-4 sm:mx-0">
        <div class="flex overflow-x-auto snap-x snap-mandatory h-full no-scrollbar">
            @foreach($images as $index => $image)
                <div class="flex-shrink-0 w-full h-full snap-center relative" onclick="openLightbox({{ $index }})">
                    <img src="{{ $image }}" alt="{{ $title }}" class="w-full h-full object-cover">
                    <div class="absolute bottom-4 right-4 bg-black/60 text-white px-3 py-1 rounded-full text-xs">
                        {{ $index + 1 }} / {{ count($images) }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Lightbox Modal (Simplified Implementation) -->
<div id="lightbox-modal" class="fixed inset-0 z-50 bg-black/95 hidden">
    <!-- Close Button -->
    <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300 z-50">&times;</button>

    <!-- Main Image Container -->
    <div class="relative w-full h-full flex items-center justify-center p-4">
        <img id="lightbox-img" src="" class="max-w-full max-h-full object-contain shadow-2xl rounded-lg">

        <!-- Navigation Buttons -->
        <button onclick="prevImage()" class="absolute left-4 top-1/2 -translate-y-1/2 text-white text-3xl bg-black/50 hover:bg-black/70 w-12 h-12 rounded-full flex items-center justify-center transition-all">&#10094;</button>
        <button onclick="nextImage()" class="absolute right-4 top-1/2 -translate-y-1/2 text-white text-3xl bg-black/50 hover:bg-black/70 w-12 h-12 rounded-full flex items-center justify-center transition-all">&#10095;</button>
    </div>

    <!-- Caption/Counter -->
    <div class="absolute bottom-6 text-white text-sm font-medium w-full text-center">
        <span id="lightbox-counter">1 / 1</span>
    </div>
</div>

@push('scripts')
<script>
    let currentImageIndex = 0;
    const galleryImages = @json($images);

    window.openLightbox = function(index) {
        if (!galleryImages.length) return;
        currentImageIndex = index;
        updateLightbox();
        const modal = document.getElementById('lightbox-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex', 'flex-col', 'items-center', 'justify-center');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    window.closeLightbox = function() {
        const modal = document.getElementById('lightbox-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex', 'flex-col', 'items-center', 'justify-center');
        document.body.style.overflow = '';
    }

    window.nextImage = function(e) {
        if(e) e.stopPropagation();
        currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
        updateLightbox();
    }

    window.prevImage = function(e) {
        if(e) e.stopPropagation();
        currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
        updateLightbox();
    }

    function updateLightbox() {
        const img = document.getElementById('lightbox-img');
        const counter = document.getElementById('lightbox-counter');

        // Add fade effect
        img.style.opacity = '0.5';
        setTimeout(() => {
            img.src = galleryImages[currentImageIndex];
            img.style.opacity = '1';
        }, 150);

        counter.textContent = `${currentImageIndex + 1} / ${galleryImages.length}`;
    }

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (document.getElementById('lightbox-modal').classList.contains('hidden')) return;

        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'ArrowLeft') prevImage();
    });
</script>
@endpush
