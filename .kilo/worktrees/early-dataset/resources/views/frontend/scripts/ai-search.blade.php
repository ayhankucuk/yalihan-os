<script src="/js/utils/sanitize.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('ai-search-input');
        const resultsContainer = document.getElementById('search-results');
        let debounceTimer;

        // This partial is included on multiple pages; skip wiring when search UI is absent.
        if (!searchInput || !resultsContainer) {
            return;
        }

        // Real Data Search Function
        async function fetchResults(query) {
            if (query.length < 2) {
                resultsContainer.classList.add('hidden');
                return;
            }

            try {
                // Using the simple public search endpoint (patched to support query text)
                const response = await fetch(`/api/v1/public-ai/ilan-arama`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        query: query
                    })
                });

                const data = await response.json();

                if (data.success && data.results.length > 0) {
                    renderResults(data.results);
                } else {
                    renderNoResults();
                }
            } catch (error) {
                console.error('Search error:', error);
                // Fail silently or show error
            }
        }

        function renderResults(results) {
            let html = '<ul class="divide-y divide-gray-100 dark:divide-gray-700">';

            // Limit to 5 results
            results.slice(0, 5).forEach(item => {
                // Format Price
                const priceFormatted = new Intl.NumberFormat('tr-TR', {
                    style: 'currency',
                    currency: 'TRY'
                }).format(item.price);

                html += `
                    <li class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer" onclick="window.location.href='/ilanlar/${item.id}'">
                        <div class="px-6 py-4 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-xs sm:max-w-md dark:text-slate-100">${escapeHtml(item.title)}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${escapeHtml(item.location.city)}, ${escapeHtml(item.location.district)}</p>
                            </div>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap ml-4">${priceFormatted}</span>
                        </div>
                    </li>
                `;
            });

            // "View All" Link
            const query = searchInput.value;
            html += `
                <li class="bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors cursor-pointer text-center py-3" onclick="window.location.href='/ai/explore?q=${encodeURIComponent(query)}'">
                    <span class="text-blue-700 dark:text-blue-300 font-bold text-sm">"${escapeHtml(query)}" ile ilgili tüm sonuçları gör</span>
                </li>
            `;
            html += '</ul>';

            resultsContainer.innerHTML = html;
            resultsContainer.classList.remove('hidden');
        }

        function renderNoResults() {
            const query = searchInput.value;
            resultsContainer.innerHTML = `
                <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    <p>Sonuç bulunamadı.</p>
                    <button onclick="window.location.href='/ai/explore?q=${encodeURIComponent(query)}'" class="mt-2 text-blue-600 font-bold hover:underline text-sm">
                        Yine de detaylı aramaya git &rarr;
                    </button>
                </div>
            `;
            resultsContainer.classList.remove('hidden');
        }

        // Event Listeners
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchResults(e.target.value);
            }, 300); // 300ms debounce
        });

        // Hide when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.classList.add('hidden');
            }
        });

        // Focus show results if existed
        searchInput.addEventListener('focus', () => {
            if (searchInput.value.length >= 2 && resultsContainer.innerHTML !== '') {
                resultsContainer.classList.remove('hidden');
            }
        });

        // Handle Enter Key
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const query = searchInput.value;
                if (query) {
                    window.location.href = `/ai/explore?q=${encodeURIComponent(query)}`;
                }
            }
        });
    });
</script>
<style>
    @keyframes fade-in-down {
        0% {
            opacity: 0;
            transform: translateY(-20px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fade-in-up {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-down {
        animation: fade-in-down 1s ease-out;
    }

    .animate-fade-in-up {
        animation: fade-in-up 1s ease-out 0.5s backwards;
    }
</style>
