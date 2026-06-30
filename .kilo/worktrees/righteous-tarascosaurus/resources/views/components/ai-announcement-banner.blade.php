{{-- AI Announcement Banner --}}
<div id="ai-announcement" class="fixed top-0 left-0 right-0 z-50 transform transition-transform duration-500 ease-in-out">
    <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-cyan-600 text-white py-3 px-6">
        <div class="container mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3">
                {{-- AI Icon --}}
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center animate-pulse dark:bg-slate-900/20">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>

                {{-- Message --}}
                <div class="flex-1">
                    <span class="text-sm md:text-base font-medium">
                        🎉 <strong>YENİ:</strong> AI destekli emlak arama sistemi artık status!
                        <span class="hidden md:inline">Doğal dilde arama yapın, yapay zeka size en uygun sonuçları
                            bulsun.</span>
                    </span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center space-x-3">
                <a href="#ai-search"
                    class="bg-white/20 hover:bg-white/30 px-4 py-1 rounded-full text-sm font-medium transition-colors duration-300 dark:bg-slate-900/20">
                    Dene
                </a>

                <button id="close-banner" class="hover:bg-white/20 rounded-full p-1 transition-colors duration-300">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="h-1 bg-gradient-to-r from-cyan-400 via-blue-400 to-purple-400">
        <div id="progress-bar" class="h-full bg-white/30 transition-all duration-300 dark:bg-slate-900/30" style="width: 100%;"></div>
    </div>
</div>

{{-- Body Padding to Account for Banner --}}
<div id="banner-spacer" class="transition-all duration-500 ease-in-out" style="height: 60px;"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('ai-announcement');
        const closeBanner = document.getElementById('close-banner');
        const bannerSpacer = document.getElementById('banner-spacer');
        const progressBar = document.getElementById('progress-bar');

        // Check if banner was previously closed
        const bannerClosed = localStorage.getItem('ai-banner-closed');

        if (bannerClosed) {
            hideBanner();
        } else {
            // Auto-hide after 10 seconds
            setTimeout(() => {
                if (!banner.classList.contains('hidden')) {
                    hideBanner();
                }
            }, 10000);

            // Animate progress bar
            let progress = 100;
            const interval = setInterval(() => {
                progress -= 1;
                progressBar.style.width = progress + '%';

                if (progress <= 0) {
                    clearInterval(interval);
                }
            }, 100);
        }

        // Close banner manually
        closeBanner.addEventListener('click', function() {
            hideBanner();
            localStorage.setItem('ai-banner-closed', 'true');
        });

        // Hide banner function
        function hideBanner() {
            banner.style.transform = 'translateY(-100%)';
            bannerSpacer.style.height = '0px';

            setTimeout(() => {
                banner.classList.add('hidden');
            }, 500);
        }

        // Smooth scroll to AI search
        document.querySelector('a[href="#ai-search"]')?.addEventListener('click', function(e) {
            e.preventDefault();
            const searchSection = document.querySelector('#ai-search-input')?.closest('section');
            if (searchSection) {
                searchSection.scrollIntoView({
                    behavior: 'smooth'
                });
                // Focus on search input after scroll
                setTimeout(() => {
                    document.querySelector('#ai-search-input')?.focus();
                }, 800);
            }
        });
    });
</script>

<style>
    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    #ai-announcement {
        background: linear-gradient(90deg, #2563eb, #7c3aed, #0891b2, #2563eb);
        background-size: 200% 100%;
        animation: shimmer 3s ease-in-out infinite;
    }
</style>
