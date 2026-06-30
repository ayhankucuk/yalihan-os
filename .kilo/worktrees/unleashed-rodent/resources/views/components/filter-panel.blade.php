<!-- Filter Panel Component -->
@props([
    'action' => '',
    'searchValue' => '',
    'categories' => [],
    'emlakTurleri' => [],
    'showAdvanced' => true,
    'showBulkActions' => true,
])

<style>
    .ultra-modern-filter-panel {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }

    .ultra-modern-input {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 2px solid transparent;
        border-radius: 16px;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .ultra-modern-input:focus {
        background: rgba(255, 255, 255, 0.95);
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
    }

    .ultra-modern-select {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 2px solid transparent;
        border-radius: 16px;
        transition: all 0.3s ease;
        font-weight: 500;
        cursor: pointer;
    }

    .ultra-modern-select:focus {
        background: rgba(255, 255, 255, 0.95);
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
    }

    .ultra-modern-label {
        font-weight: 600;
        font-size: 0.95rem;
        color: #1a202c;
        margin-bottom: 0.75rem;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .ultra-modern-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 16px;
        padding: 12px 24px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .ultra-modern-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .ultra-modern-inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 transition-all duration-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 {
        background: rgba(107, 114, 128, 0.1);
        color: #4a5568;
        border: 2px solid rgba(107, 114, 128, 0.2);
        border-radius: 16px;
        padding: 12px 24px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .ultra-modern-inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 transition-all duration-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700:hover {
        background: rgba(107, 114, 128, 0.2);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(107, 114, 128, 0.2);
    }

    .ultra-modern-btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 16px;
        padding: 12px 24px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .ultra-modern-btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }

    .ultra-modern-btn-purple {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        border: none;
        border-radius: 16px;
        padding: 12px 24px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
    }

    .ultra-modern-btn-purple:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
    }

    .ultra-modern-filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 768px) {
        .ultra-modern-filters-grid {
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }
    }
</style>

<div class="ultra-modern-filter-panel p-8">
    <form method="GET" action="{{ $action }}" id="filterForm">
        <div class="ultra-modern-filters-grid">
            <!-- Genel Arama -->
            <div>
                <label class="ultra-modern-label">
                    🔍 Genel Arama
                </label>
                <input type="text" name="search" value="{{ $searchValue }}"
                    placeholder="İlan sahibi, adres, apartman adı..." class="ultra-modern-input w-full px-4 py-3">
            </div>

            <!-- İlan Türü -->
            <div>
                <label class="ultra-modern-label">
                    🏠 İlan Türü
                </label>
                <select name="ilan_turu" class="ultra-modern-select w-full px-4 py-3">
                    <option value="">🎯 Tümü</option>
                    @foreach ($emlakTurleri as $tur)
                        <option value="{{ $tur }}" {{ request('ilan_turu') == $tur ? 'selected' : '' }}>
                            {{ $tur }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Kategori -->
            @if (!empty($categories))
                <div>
                    <label class="ultra-modern-label">
                        📂 Kategori
                    </label>
                    <select name="ana_kategori_id" class="ultra-modern-select w-full px-4 py-3">
                        <option value="">📋 Tüm Kategoriler</option>
                        @foreach ($categories as $kategori)
                            <option value="{{ $kategori->id }}"
                                {{ request('ana_kategori_id') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Durum -->
            <div>
                <label class="ultra-modern-label">
                    ⚡ Durum
                </label>
                <select name="status" class="ultra-modern-select w-full px-4 py-3">
                    <option value="">🌟 Tüm Durumlar</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>✅ Aktif</option>
                    <option value="pasif" {{ request('status') == 'pasif' ? 'selected' : '' }}>⏸️ Pasif</option>
                    <option value="taslak" {{ request('status') == 'taslak' ? 'selected' : '' }}>✏️ Taslak</option>
                    <option value="satildi" {{ request('status') == 'satildi' ? 'selected' : '' }}>💰 Satıldı</option>
                    <option value="kiralandi" {{ request('status') == 'kiralandi' ? 'selected' : '' }}>🔑 Kiralandı
                    </option>
                </select>
            </div>

            @if ($showAdvanced)
                <!-- Fiyat Aralığı -->
                <div>
                    <label class="ultra-modern-label">
                        💵 Min Fiyat
                    </label>
                    <input type="number" name="min_fiyat" value="{{ request('min_fiyat') }}" placeholder="₺ 0"
                        class="ultra-modern-input w-full px-4 py-3">
                </div>

                <div>
                    <label class="ultra-modern-label">
                        💰 Max Fiyat
                    </label>
                    <input type="number" name="max_fiyat" value="{{ request('max_fiyat') }}" placeholder="₺ ∞"
                        class="ultra-modern-input w-full px-4 py-3">
                </div>

                <!-- Tarih Aralığı -->
                <div>
                    <label class="ultra-modern-label">
                        📅 Başlangıç Tarihi
                    </label>
                    <input type="date" name="baslangic_tarihi" value="{{ request('baslangic_tarihi') }}"
                        class="ultra-modern-input w-full px-4 py-3">
                </div>

                <div>
                    <label class="ultra-modern-label">
                        📅 Bitiş Tarihi
                    </label>
                    <input type="date" name="bitis_tarihi" value="{{ request('bitis_tarihi') }}"
                        class="ultra-modern-input w-full px-4 py-3">
                </div>
            @endif
        </div>

        <!-- Ultra Modern Filter Actions -->
        <div class="flex flex-wrap gap-4 justify-center">
            <button type="submit" class="ultra-modern-btn flex items-center space-x-2">
                <span class="material-symbols-outlined">search</span>
                <span>Filtrele & Ara</span>
            </button>

            <a href="{{ $action }}" class="ultra-modern-inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 inline-flex border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 flex space-x-2 dark:text-slate-300">
                <span class="material-symbols-outlined">refresh</span>
                <span>Tümünü Göster</span>
            </a>

            @if ($showBulkActions)
                <button type="button" onclick="toggleBulkActions()"
                    class="ultra-modern-btn-success flex items-center space-x-2">
                    <span class="material-symbols-outlined">done_all</span>
                    <span>Toplu İşlemler</span>
                </button>
            @endif

            @if ($showAdvanced)
                <button type="button" onclick="toggleAdvancedFilters()"
                    class="ultra-modern-btn-purple flex items-center space-x-2">
                    <span class="material-symbols-outlined">tune</span>
                    <span id="advancedToggleText">Gelişmiş Filtreler</span>
                </button>
            @endif
        </div>
    </form>
</div>

<script>
    function toggleAdvancedFilters() {
        const advancedInputs = document.querySelectorAll(
            'input[name="min_fiyat"], input[name="max_fiyat"], input[name="baslangic_tarihi"], input[name="bitis_tarihi"]'
        );
        const toggleText = document.getElementById('advancedToggleText');
        const isVisible = !advancedInputs[0].closest('.ultra-modern-filters-grid').classList.contains('show-advanced');

        advancedInputs.forEach(input => {
            const container = input.closest('div');
            if (isVisible) {
                container.style.display = 'block';
                container.style.opacity = '0';
                container.style.transform = 'translateY(-20px)';

                // Smooth animation
                setTimeout(() => {
                    container.style.transition = 'all 0.3s ease';
                    container.style.opacity = '1';
                    container.style.transform = 'translateY(0)';
                }, 50);
            } else {
                container.style.transition = 'all 0.3s ease';
                container.style.opacity = '0';
                container.style.transform = 'translateY(-20px)';

                setTimeout(() => {
                    container.style.display = 'none';
                }, 300);
            }
        });

        // Toggle class and button text
        const grid = document.querySelector('.ultra-modern-filters-grid');
        grid.classList.toggle('show-advanced');

        if (toggleText) {
            toggleText.textContent = isVisible ? 'Basit Görünüm' : 'Gelişmiş Filtreler';
        }
    }

    // Enhanced initialization
    document.addEventListener('DOMContentLoaded', function() {
        const advancedInputs = document.querySelectorAll(
            'input[name="min_fiyat"], input[name="max_fiyat"], input[name="baslangic_tarihi"], input[name="bitis_tarihi"]'
        );

        // Check if there are values
        let hasAdvancedValues = false;
        advancedInputs.forEach(input => {
            if (input.value) {
                hasAdvancedValues = true;
            }
        });

        // Hide if no values, show with animation if values exist
        if (!hasAdvancedValues) {
            advancedInputs.forEach(input => {
                input.closest('div').style.display = 'none';
            });
        } else {
            document.querySelector('.ultra-modern-filters-grid').classList.add('show-advanced');
            document.getElementById('advancedToggleText').textContent = 'Basit Görünüm';

            // Show with smooth animation
            advancedInputs.forEach((input, index) => {
                const container = input.closest('div');
                container.style.opacity = '0';
                container.style.transform = 'translateY(-20px)';

                setTimeout(() => {
                    container.style.transition = 'all 0.3s ease';
                    container.style.opacity = '1';
                    container.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }

        // Add focus effects
        document.querySelectorAll('.ultra-modern-input, .ultra-modern-select').forEach(element => {
            element.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
            });

            element.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
</script>
