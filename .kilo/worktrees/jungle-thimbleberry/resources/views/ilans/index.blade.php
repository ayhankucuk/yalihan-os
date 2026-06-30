@extends('admin.layouts.admin')

@section('title', 'İlan Yönetimi')

@push('styles')
    <style>
        /* Modern İlan Yönetimi Stiller */
        .modern-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .modern-stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .modern-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .modern-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .modern-stat-card:hover::before {
            transform: translateX(0);
        }

        .modern-stat-card.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .modern-stat-card.green {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .modern-stat-card.orange {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .modern-stat-card.purple {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }

        .modern-listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .modern-listing-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            position: relative;
        }

        .modern-listing-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #3b82f6;
        }

        .listing-quick-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modern-listing-card:hover .listing-quick-actions {
            opacity: 1;
        }

        .modern-filter-panel {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .modern-filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .modern-stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .modern-listings-grid {
                grid-template-columns: 1fr;
            }

            .modern-filters-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (min-width: 768px) and (max-width: 1024px) {
            .modern-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .modern-listings-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-4 py-6">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        <span class="material-symbols-outlined text-blue-600 mr-3">apartment</span>
                        İlan Yönetimi
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Tüm ilanları yönetin, durumlarını takip edin ve sistem geneli kontrol sağlayın.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 mt-4 lg:mt-0">
                    <a href="{{ route('admin.ilanlar.create') }}"
                        class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 text-white px-6 py-2.5 rounded-lg font-medium transition-colors flex items-center">
                        <span class="material-symbols-outlined mr-2">add</span>
                        Yeni İlan
                    </a>
                    <button onclick="refreshStats()"
                        class="bg-gray-100 hover:bg-gray-200 dark:bg-slate-900 dark:hover:bg-gray-700 text-gray-700 dark:text-slate-100 px-6 py-2.5 rounded-lg font-medium transition-colors flex items-center border border-transparent dark:border-slate-800 dark:text-slate-300">
                        <span class="material-symbols-outlined mr-2">sync</span>
                        Yenile
                    </button>
                </div>
            </div>

            <!-- Modern Statistics Cards -->
            <div class="modern-stats-grid">
                <div class="modern-stat-card blue">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Toplam İlan</h3>
                            <p class="text-3xl font-bold mt-1">{{ $istatistikler['toplam'] ?? 0 }}</p>
                        </div>
                        <div class="text-4xl opacity-80">
                            <span class="material-symbols-outlined">home</span>
                        </div>
                    </div>
                </div>

                <div class="modern-stat-card green">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Aktif İlan</h3>
                            <p class="text-3xl font-bold mt-1">{{ $istatistikler['active'] ?? 0 }}</p>
                        </div>
                        <div class="text-4xl opacity-80">
                            <span class="material-symbols-outlined">visibility</span>
                        </div>
                    </div>
                </div>

                <div class="modern-stat-card orange">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Taslak</h3>
                            <p class="text-3xl font-bold mt-1">{{ $istatistikler['taslak'] ?? 0 }}</p>
                        </div>
                        <div class="text-4xl opacity-80">
                            <span class="material-symbols-outlined">edit</span>
                        </div>
                    </div>
                </div>

                <div class="modern-stat-card purple">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Satıldı</h3>
                            <p class="text-3xl font-bold mt-1">{{ $istatistikler['satildi'] ?? 0 }}</p>
                        </div>
                        <div class="text-4xl opacity-80">
                            <span class="material-symbols-outlined">handshake</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tab Navigation -->
        <div class="admin-tab-nav">
            <button class="admin-tab-btn admin-tab-btn-active" data-tab="all" onclick="switchTab('all')">
                <span>Tüm İlanlar</span>
                <span class="admin-tab-count">{{ $istatistikler['total_listings'] ?? 0 }}</span>
            </button>
            <button class="admin-tab-btn" data-tab="active" onclick="switchTab('active')">
                <span>Aktif</span>
                <span class="admin-tab-count">{{ $istatistikler['active_listings'] ?? 0 }}</span>
            </button>
            <button class="admin-tab-btn" data-tab="draft" onclick="switchTab('draft')">
                <span>Taslak</span>
                <span class="admin-tab-count">{{ $istatistikler['draft_listings'] ?? 0 }}</span>
            </button>
            <button class="admin-tab-btn" data-tab="expired" onclick="switchTab('expired')">
                <span>Süresi Dolan</span>
                <span class="admin-tab-count">{{ $istatistikler['expired_listings'] ?? 0 }}</span>
            </button>
            <button class="admin-tab-btn" data-tab="pending" onclick="switchTab('pending')">
                <span>Beklemede</span>
                <span class="admin-tab-count">{{ $istatistikler['pending_listings'] ?? 0 }}</span>
            </button>
            <button class="admin-tab-btn" data-tab="synced" onclick="switchTab('synced')">
                <span>Portal Sync</span>
                <span class="admin-tab-count">{{ $istatistikler['synced_listings'] ?? 0 }}</span>
            </button>
        </div>

        <!-- Filters -->
        <div class="admin-filters-section">
            <div class="admin-filters-header">
                <button class="admin-filters-toggle" onclick="toggleFilters()">
                    <span class="material-symbols-outlined">filter_list</span>
                    <span>Filtreler</span>
                    <span class="material-symbols-outlined admin-filters-arrow">expand_more</span>
                </button>
            </div>
            <div class="admin-filters-content" id="filtersContent">
                <form method="GET" class="admin-filters-form">
                    <div class="admin-form-group">
                        <label class="admin-form-label">Arama</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="İlan başlığı, açıklama ara..."
                            class="admin-form-input bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 border border-gray-300 dark:border-slate-800 dark:text-white">
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Kategori</label>
                        <select name="kategori"
                            class="admin-form-select bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 border border-gray-300 dark:border-slate-800 dark:text-white">
                            <option value="">Tüm Kategoriler</option>
                            @if (isset($kategoriler) && $kategoriler->count() > 0)
                                @foreach ($kategoriler as $kategori)
                                    <option value="{{ $kategori->id }}"
                                        {{ request('kategori') == $kategori->id ? 'selected' : '' }}>
                                        {{ $kategori->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Danışman</label>
                        <select name="danisman"
                            class="admin-form-select bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 border border-gray-300 dark:border-slate-800 dark:text-white">
                            <option value="">Tüm Danışmanlar</option>
                            <!-- Danışman listesi buraya eklenecek -->
                        </select>
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Tarih Aralığı</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="admin-form-input bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 border border-gray-300 dark:border-slate-800 dark:text-white">
                    </div>
                    <div class="admin-filters-actions">
                        <button type="submit"
                            class="admin-btn admin-inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 inline-flex bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none">
                            <span class="material-symbols-outlined">search</span>
                            <span>Filtrele</span>
                        </button>
                        <a href="{{ route('admin.ilanlar.index') }}"
                            class="admin-btn admin-inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 inline-flex border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                            <span class="material-symbols-outlined">close</span>
                            <span>Temizle</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modern Advanced Filters -->
        <div class="modern-filter-panel">
            <form method="GET" action="{{ route('admin.ilanlar.index') }}" id="filterForm">
                <div class="modern-filters-grid">
                    <!-- Arama -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Genel Arama</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="İlan başlığı, danışman, adres..."
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-800 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 dark:text-white">
                    </div>

                    <!-- İlan Türü -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">İlan Türü</label>
                        <select name="ilan_turu"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-800 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 dark:text-white">
                            <option value="">Tümü</option>
                            @foreach ($emlakTurleri as $tur)
                                <option value="{{ $tur }}" {{ request('ilan_turu') == $tur ? 'selected' : '' }}>
                                    {{ $tur }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kategori -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Kategori</label>
                        <select name="ana_kategori_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Tüm Kategoriler</option>
                            @foreach ($anaKategoriler as $kategori)
                                <option value="{{ $kategori->id }}"
                                    {{ request('ana_kategori_id') == $kategori->id ? 'selected' : '' }}>
                                    {{ $kategori->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Durum -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Durum</label>
                        <select name="yayin_statusu"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Tüm Durumlar</option>
                            <option value="active" {{ request('yayin_statusu') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="pasif" {{ request('yayin_statusu') == 'pasif' ? 'selected' : '' }}>Pasif</option>
                            <option value="taslak" {{ request('yayin_statusu') == 'taslak' ? 'selected' : '' }}>Taslak</option>
                            <option value="satildi" {{ request('yayin_statusu') == 'satildi' ? 'selected' : '' }}>Satıldı
                            </option>
                        </select>
                    </div>

                    <!-- Fiyat Aralığı -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Min Fiyat</label>
                        <input type="number" name="min_fiyat" value="{{ request('min_fiyat') }}"
                            placeholder="Min fiyat"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Max Fiyat</label>
                        <input type="number" name="max_fiyat" value="{{ request('max_fiyat') }}"
                            placeholder="Max fiyat"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 mt-4">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center">
                        <span class="material-symbols-outlined mr-2">search</span>
                        Ara
                    </button>
                    <a href="{{ route('admin.ilanlar.index') }}"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-medium transition-colors flex items-center dark:text-slate-300 dark:bg-slate-900">
                        <span class="material-symbols-outlined mr-2">close</span>
                        Temizle
                    </a>
                    <button type="button" onclick="toggleBulkActions()"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center">
                        <span class="material-symbols-outlined mr-2">check_box</span>
                        Toplu Seç
                    </button>
                </div>
            </form>
        </div>

        <!-- Bulk Actions Panel -->
        <div id="bulkActionsPanel" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-slate-300">
                        <span id="selectedCount">0</span> ilan seçildi
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="bulkAction('activate')"
                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm">
                        <span class="material-symbols-outlined mr-1">visibility</span> Aktifleştir
                    </button>
                    <button type="button" onclick="bulkAction('deactivate')"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1.5 rounded text-sm">
                        <span class="material-symbols-outlined mr-1">visibility_off</span> Pasifleştir
                    </button>
                    <button type="button" onclick="bulkAction('delete')"
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-sm">
                        <span class="material-symbols-outlined mr-1">delete</span> Sil
                    </button>
                </div>
            </div>
        </div>

        <!-- Modern Listings Grid -->
        <div id="listingsContainer">
            @if ($ilanlar->count() > 0)
                <div class="modern-listings-grid">
                    @foreach ($ilanlar as $ilan)
                        <div class="modern-listing-card">
                            <!-- Selection Checkbox -->
                            <div class="absolute top-3 left-3 z-20">
                                <input type="checkbox" value="{{ $ilan->id }}"
                                    class="listing-checkbox w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:bg-slate-900">
                            </div>

                            <!-- Quick Actions -->
                            <div class="listing-quick-actions">
                                <div class="flex space-x-2">
                                    <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                                        class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg text-sm transition-colors">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </a>
                                    <a href="{{ route('admin.ilanlar.edit', $ilan->id) }}"
                                        class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-lg text-sm transition-colors">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <button onclick="deleteListing({{ $ilan->id }})"
                                        class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg text-sm transition-colors">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="mb-3">
                                @if ($ilan->status_mi)
                                    <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-full text-xs font-medium">
                                        <span class="material-symbols-outlined mr-1">visibility</span> Aktif
                                    </span>
                                @else
                                    <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-slate-200 px-2 py-1 rounded-full text-xs font-medium dark:bg-slate-900">
                                        <span class="material-symbols-outlined mr-1">visibility_off</span> Pasif
                                    </span>
                                @endif

                                {{-- Context7: Accessor üzerinden status erişimi --}}
                                @php
                                    $ilanDurumu = $ilan->yayin_statusu ?? 'Taslak';
                                @endphp
                                @if ($ilanDurumu == 'satildi')
                                    <span
                                        class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded-full text-xs font-medium ml-2">
                                        <span class="material-symbols-outlined mr-1">handshake</span> Satıldı
                                    </span>
                                @elseif ($ilanDurumu == 'kiralandi')
                                    <span
                                        class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-medium ml-2">
                                        <span class="material-symbols-outlined mr-1">key</span> Kiralandı
                                    </span>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2 dark:text-slate-100 dark:text-white">
                                    {{ $ilan->baslik ?? 'Başlık Yok' }}
                                </h3>

                                <div class="text-sm text-gray-600 space-y-1">
                                    <div class="flex items-center">
                                        <span class="material-symbols-outlined text-gray-400 mr-2">location_on</span>
                                        <span>{{ $ilan->adres_il ?? 'İl' }} / {{ $ilan->adres_ilce ?? 'İlçe' }}</span>
                                    </div>

                                    @if ($ilan->danisman)
                                        <div class="flex items-center">
                                            <span class="material-symbols-outlined text-gray-400 mr-2">person</span>
                                            <span>{{ $ilan->danisman->ad }} {{ $ilan->danisman->soyad }}</span>
                                        </div>
                                    @endif

                                    <div class="flex items-center">
                                        <span class="material-symbols-outlined text-gray-400 mr-2">calendar_today</span>
                                        <span>{{ $ilan->created_at->format('d.m.Y') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Price -->
                            @if ($ilan->fiyat)
                                <div class="mb-4">
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ number_format($ilan->fiyat, 0, ',', '.') }} ₺
                                    </div>
                                    @if ($ilan->ilan_turu)
                                        <div class="text-sm text-gray-500">{{ $ilan->ilan_turu }}</div>
                                    @endif
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-slate-800">
                                <div class="flex space-x-2">
                                    <span class="text-xs text-gray-500">ID: {{ $ilan->id }}</span>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="toggleListingStatus({{ $ilan->id }})"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                        {{ $ilan->status_mi ? 'Pasifleştir' : 'Aktifleştir' }}
                                    </button>
                                    <button onclick="duplicateListing({{ $ilan->id }})"
                                        class="text-xs text-green-600 hover:text-green-800 font-medium">
                                        Kopyala
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $ilanlar->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-gray-500 text-lg mb-4">
                        <span class="material-symbols-outlined text-6xl mb-4">home</span>
                        <p>Herhangi bir ilan bulunamadı.</p>
                    </div>
                    <a href="{{ route('admin.ilanlar.create') }}"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center">
                        <span class="material-symbols-outlined mr-2">add</span>
                        İlk İlanını Oluştur
                    </a>
                </div>
            @endif
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Modern İlan Yönetimi JavaScript Fonksiyonları

        // Checkboxların statusunu takip et
        let selectedListings = [];

        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', function() {
            initializeListingCheckboxes();
            setupBulkActions();
        });

        // Checkbox'ları initialize et
        function initializeListingCheckboxes() {
            const checkboxes = document.querySelectorAll('.listing-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const listingId = parseInt(this.value);
                    if (this.checked) {
                        if (!selectedListings.includes(listingId)) {
                            selectedListings.push(listingId);
                        }
                    } else {
                        selectedListings = selectedListings.filter(id => id !== listingId);
                    }
                    updateBulkActionsPanel();
                });
            });
        }

        // Bulk actions panel'i güncelle
        function updateBulkActionsPanel() {
            const panel = document.getElementById('bulkActionsPanel');
            const countElement = document.getElementById('selectedCount');

            if (selectedListings.length > 0) {
                panel.classList.remove('hidden');
                countElement.textContent = selectedListings.length;
            } else {
                panel.classList.add('hidden');
            }
        }

        // Bulk actions
        function bulkAction(action) {
            if (selectedListings.length === 0) {
                alert('Lütfen işlem yapmak istediğiniz ilanları seçin.');
                return;
            }

            let confirmMessage = '';
            let endpoint = '';

            switch (action) {
                case 'activate':
                    confirmMessage = `${selectedListings.length} ilan statusleştirilecek. Devam etmek istiyor musunuz?`;
                    endpoint = '{{ route('admin.ilanlar.bulk-update') }}';
                    break;
                case 'deactivate':
                    confirmMessage = `${selectedListings.length} ilan pasifleştirilecek. Devam etmek istiyor musunuz?`;
                    endpoint = '{{ route('admin.ilanlar.bulk-update') }}';
                    break;
                case 'delete':
                    confirmMessage =
                        `${selectedListings.length} ilan silinecek. Bu işlem geri alınamaz. Devam etmek istiyor musunuz?`;
                    endpoint = '{{ route('admin.ilanlar.bulk-delete') }}';
                    break;
            }

            if (confirm(confirmMessage)) {
                performBulkAction(action, endpoint);
            }
        }

        // Bulk action AJAX call
        function performBulkAction(action, endpoint) {
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('action', action);
            selectedListings.forEach(id => {
                formData.append('listing_ids[]', id);
            });

            if (action === 'delete') {
                formData.append('_method', 'DELETE');
            }

            fetch(endpoint, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'İşlem başarıyla tamamlandı!');
                        location.reload();
                    } else {
                        alert(data.message || 'İşlem sırasında bir hata oluştu.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('İşlem sırasında bir hata oluştu.');
                });
        }

        // Tekil ilan statusu değiştir
        function toggleListingStatus(listingId) {
            if (confirm('İlan statusunu değiştirmek istediğinize emin misiniz?')) {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'PATCH');
                formData.append('action', 'toggle_status');

                fetch(`/admin/ilanlar/${listingId}`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'İşlem sırasında bir hata oluştu.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('İşlem sırasında bir hata oluştu.');
                    });
            }
        }

        // İlan sil
        function deleteListing(listingId) {
            if (confirm('Bu ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'DELETE');

                fetch(`/admin/ilanlar/${listingId}`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('İlan başarıyla silindi!');
                            location.reload();
                        } else {
                            alert(data.message || 'İşlem sırasında bir hata oluştu.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('İşlem sırasında bir hata oluştu.');
                    });
            }
        }

        // İlan kopyala
        function duplicateListing(listingId) {
            if (confirm('Bu ilanı kopyalamak istediğinize emin misiniz?')) {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');

                fetch(`/admin/ilanlar/${listingId}/duplicate`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('İlan başarıyla kopyalandı!');
                            location.reload();
                        } else {
                            alert(data.message || 'İşlem sırasında bir hata oluştu.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('İşlem sırasında bir hata oluştu.');
                    });
            }
        }

        // İstatistikleri yenile
        function refreshStats() {
            fetch('{{ route('admin.ilanlar.index') }}?ajax=1', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.stats) {
                        // İstatistikleri güncelle
                        updateStatsDisplay(data.stats);
                        alert('İstatistikler güncellendi!');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('İstatistikler güncellenirken bir hata oluştu.');
                });
        }

        // İstatistik gösterimini güncelle
        function updateStatsDisplay(stats) {
            // Bu fonksiyonu gerçek veri yapısına göre güncelleyeceğiz
            console.log('Stats updated:', stats);
        }

        // Bulk selection toggle
        function toggleBulkActions() {
            const checkboxes = document.querySelectorAll('.listing-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);

            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
                const listingId = parseInt(checkbox.value);

                if (checkbox.checked) {
                    if (!selectedListings.includes(listingId)) {
                        selectedListings.push(listingId);
                    }
                } else {
                    selectedListings = selectedListings.filter(id => id !== listingId);
                }
            });

            updateBulkActionsPanel();
        }

        // Setup bulk actions on page load
        function setupBulkActions() {
            // İlk yüklemede gizli olarak başlat
            const panel = document.getElementById('bulkActionsPanel');
            if (panel) {
                panel.classList.add('hidden');
            }
        }
    </script>
@endpush
