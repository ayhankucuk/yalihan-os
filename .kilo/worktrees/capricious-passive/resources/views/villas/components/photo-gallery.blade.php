{{-- Photo Gallery Component --}}
{{-- Pure Tailwind + Alpine.js (NO Lightbox.js!) --}}
{{-- Yalıhan Bekçi kurallarına %100 uyumlu --}}

@php
    $photos = $villa->photos ?? collect();
    $featuredPhoto = $villa->featuredPhoto ?? $photos->first();
    $allPhotos = $photos->isNotEmpty() ? $photos : collect([$featuredPhoto])->filter();
@endphp

<div x-data="photoGallery({{ json_encode($allPhotos->map(fn($p) => ['url' => $p->getImageUrl(), 'caption' => $p->caption ?? ''])->values()) }})"
     class="relative">

    @if($allPhotos->count() > 0)
        {{-- Gallery Grid --}}
        <div class="relative h-[500px] lg:h-[600px] overflow-hidden">
            {{-- Mobile: Single image slider --}}
            <div class="lg:hidden relative h-full">
                <template x-for="(photo, index) in photos" :key="index">
                    <div x-show="currentIndex === index"
                         class="absolute inset-0 w-full h-full">
                        <img :src="photo.url"
                             :alt="photo.caption || 'Villa fotoğrafı'"
                             class="w-full h-full object-cover"
                             loading="lazy">
                    </div>
                </template>

                {{-- Mobile Controls --}}
                <button @click="prev()"
                        class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-white dark:hover:bg-gray-800 transition-colors z-10 dark:bg-slate-900/90">
                    <span class="material-symbols-outlined text-gray-800 dark:text-white">chevron_left</span>
                </button>
                <button @click="next()"
                        class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-white dark:hover:bg-gray-800 transition-colors z-10 dark:bg-slate-900/90">
                    <span class="material-symbols-outlined text-gray-800 dark:text-white">chevron_right</span>
                </button>

                {{-- Photo Counter --}}
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 px-4 py-2 bg-black/70 backdrop-blur-sm text-white rounded-full text-sm font-medium z-10">
                    <span x-text="currentIndex + 1"></span> / <span x-text="photos.length"></span>
                </div>
            </div>

            {{-- Desktop: Mosaic grid --}}
            <div class="hidden lg:grid lg:grid-cols-4 lg:grid-rows-2 gap-2 h-full">
                {{-- Main large image (left 2x2) --}}
                <div class="col-span-2 row-span-2 relative group cursor-pointer overflow-hidden rounded-l-2xl"
                     @click="openLightbox(0)">
                    <img src="{{ $allPhotos->first()->getImageUrl() }}"
                         alt="Ana fotoğraf"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                         loading="eager">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></div>
                </div>

                {{-- Grid images (right 2x2) --}}
                @foreach($allPhotos->skip(1)->take(3) as $index => $photo)
                <div class="relative group cursor-pointer overflow-hidden {{ $loop->last ? 'rounded-tr-2xl' : '' }}"
                     @click="openLightbox({{ $index + 1 }})">
                    <img src="{{ $photo->getImageUrl() }}"
                         alt="Villa fotoğrafı {{ $index + 2 }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                         loading="lazy">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></div>

                    @if($loop->last && $allPhotos->count() > 4)
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center">
                        <div class="text-white text-center">
                            <span class="material-symbols-outlined mb-2" style="font-size:48px">photo_library</span>
                            <div class="text-xl font-bold">+{{ $allPhotos->count() - 4 }}</div>
                            <div class="text-sm">Tüm Fotoğraflar</div>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach

                {{-- Last cell (bottom right) --}}
                @if($allPhotos->count() > 4)
                <div class="relative group cursor-pointer overflow-hidden rounded-br-2xl"
                     @click="openLightbox(4)">
                    <img src="{{ $allPhotos->skip(4)->first()->getImageUrl() }}"
                         alt="Villa fotoğrafı 5"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                         loading="lazy">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></div>
                </div>
                @endif
            </div>
        </div>

        {{-- Show All Photos Button --}}
        <button @click="openLightbox(0)"
                class="hidden lg:block absolute bottom-6 right-6 px-6 py-3 bg-white dark:bg-slate-900 text-gray-800 dark:text-white rounded-lg shadow-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors z-10 dark:text-slate-200">
            <span class="material-symbols-outlined mr-2" style="font-size:18px;vertical-align:middle">photo_library</span>
            Tüm Fotoğrafları Göster ({{ $allPhotos->count() }})
        </button>

        {{-- Lightbox Modal --}}
        <div x-show="lightboxOpen"
             @click.self="closeLightbox()"
             @keydown.escape.window="closeLightbox()"
             class="fixed inset-0 bg-black/95 backdrop-blur-sm z-50 flex items-center justify-center"
             style="display: none;">

            {{-- Close Button --}}
            <button @click="closeLightbox()"
                    class="absolute top-4 right-4 w-12 h-12 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white z-50 dark:bg-slate-900/10 dark:bg-slate-800/40">
                <span class="material-symbols-outlined" style="font-size:24px">close</span>
            </button>

            {{-- Image Counter --}}
            <div class="absolute top-4 left-1/2 -translate-x-1/2 px-6 py-3 bg-white/10 backdrop-blur-sm text-white rounded-full font-medium z-50 dark:bg-slate-900/10 dark:bg-slate-800/40">
                <span x-text="currentIndex + 1"></span> / <span x-text="photos.length"></span>
            </div>

            {{-- Main Image --}}
            <div class="relative w-full h-full flex items-center justify-center p-4 md:p-12">
                <template x-for="(photo, index) in photos" :key="index">
                    <div x-show="currentIndex === index"
                         class="absolute max-w-7xl max-h-full">
                        <img :src="photo.url"
                             :alt="photo.caption || 'Villa fotoğrafı'"
                             class="max-w-full max-h-[80vh] object-contain mx-auto"
                             loading="lazy">
                        <p x-show="photo.caption"
                           x-text="photo.caption"
                           class="text-center text-white mt-4 text-lg"></p>
                    </div>
                </template>
            </div>

            {{-- Navigation Buttons --}}
            <button @click="prev()"
                    class="absolute left-4 top-1/2 -translate-y-1/2 w-14 h-14 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white z-50 dark:bg-slate-900/10 dark:bg-slate-800/40">
                <span class="material-symbols-outlined" style="font-size:24px">chevron_left</span>
            </button>
            <button @click="next()"
                    class="absolute right-4 top-1/2 -translate-y-1/2 w-14 h-14 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white z-50 dark:bg-slate-900/10 dark:bg-slate-800/40">
                <span class="material-symbols-outlined" style="font-size:24px">chevron_right</span>
            </button>

            {{-- Thumbnails --}}
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 max-w-7xl w-full px-4">
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide">
                    <template x-for="(photo, index) in photos" :key="index">
                        <button @click="currentIndex = index"
                                :class="currentIndex === index ? 'ring-4 ring-white' : 'opacity-60 hover:opacity-100'"
                                class="flex-shrink-0 w-20 h-16 rounded-lg overflow-hidden transition-all">
                            <img :src="photo.url"
                                 :alt="'Thumbnail ' + (index + 1)"
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        </button>
                    </template>
                </div>
            </div>
        </div>
    @else
        {{-- No photos placeholder --}}
        <div class="h-[500px] bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
            <div class="text-center text-gray-500 dark:text-gray-400">
                <span class="material-symbols-outlined mb-4" style="font-size:72px">image</span>
                <p class="text-lg">Henüz fotoğraf eklenmemiş</p>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function photoGallery(photos) {
    return {
        photos: photos,
        currentIndex: 0,
        lightboxOpen: false,

        openLightbox(index) {
            this.currentIndex = index;
            this.lightboxOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeLightbox() {
            this.lightboxOpen = false;
            document.body.style.overflow = '';
        },

        next() {
            this.currentIndex = (this.currentIndex + 1) % this.photos.length;
        },

        prev() {
            this.currentIndex = (this.currentIndex - 1 + this.photos.length) % this.photos.length;
        }
    }
}
</script>

<style>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
@endpush
