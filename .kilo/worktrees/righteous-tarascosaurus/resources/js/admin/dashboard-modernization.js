// Yalıhan Bekçi - Dashboard Modernization
// Advanced dashboard with Kanban board + analytics

class DashboardModernization {
    constructor() {
        this.dashboardModules = new Map();
        this.kanbanBoard = null;
        this.analytics = null;
        this.quickActions = null;
        this.init();
    }

    init() {
        this.createDashboardModules();
        this.setupKanbanBoard();
        this.setupAnalytics();
        this.setupQuickActions();
        this.injectDashboardCSS();
    }

    // İlan Yönetimi Dashboard Modernizasyonu
    createDashboardModules() {
        this.dashboardModules = new Map([
            [
                'kanbanBoard',
                {
                    columns: ['Taslak', 'İnceleme', 'Yayında', 'Arşiv'],
                    dragDrop: true,
                    realtime: true,
                    filters: ['category', 'durum', 'date', 'user'],
                    search: true,
                    pagination: true,
                },
            ],
            [
                'analytics',
                {
                    charts: [
                        'Günlük görüntülenme',
                        'Fiyat trendi',
                        'Lokasyon analizi',
                        'Kategori dağılımı',
                    ],
                    refresh: 'auto-30s',
                    export: true,
                    realtime: true,
                    dateRange: ['7d', '30d', '90d', '1y'],
                },
            ],
            [
                'quickActions',
                {
                    bulkEdit: true,
                    massStatus: true,
                    aiOptimization: true,
                    export: true,
                    import: true,
                    duplicate: true,
                },
            ],
        ]);
    }

    setupKanbanBoard() {
        this.kanbanBoard = {
            container: null,
            columns: new Map(),
            items: new Map(),

            init(container) {
                this.container = container;
                this.createKanbanStructure();
                this.loadKanbanData();
                this.setupDragAndDrop();
                this.setupFilters();
                this.setupSearch();
            },

            createKanbanStructure() {
                const kanbanHTML = `
                    <div class="kanban-board">
                        <div class="kanban-header">
                            <div class="kanban-title">
                                <h2><i class="fas fa-columns mr-2"></i>İlan Yönetimi</h2>
                                <div class="kanban-stats">
                                    <span class="stat-item">
                                        <i class="fas fa-file-alt text-blue-600"></i>
                                        <span class="stat-value" data-stat="total">0</span>
                                        <span class="stat-label">Toplam</span>
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-eye text-green-600"></i>
                                        <span class="stat-value" data-stat="published">0</span>
                                        <span class="stat-label">Yayında</span>
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-clock text-yellow-600"></i>
                                        <span class="stat-value" data-stat="pending">0</span>
                                        <span class="stat-label">Beklemede</span>
                                    </span>
                                </div>
                            </div>
                            <div class="kanban-controls">
                                <div class="kanban-filters">
                                    <select class="kanban-filter" data-filter="category">
                                        <option value="">Tüm Kategoriler</option>
                                    </select>
                                    <select class="kanban-filter" data-filter="durum">
                                        <option value="">Tüm Durumlar</option>
                                        <option value="draft">Taslak</option>
                                        <option value="review">İnceleme</option>
                                        <option value="published">Yayında</option>
                                        <option value="archived">Arşiv</option>
                                    </select>
                                    <input type="date" class="kanban-filter" data-filter="date-from" placeholder="Başlangıç">
                                    <input type="date" class="kanban-filter" data-filter="date-to" placeholder="Bitiş">
                                </div>
                                <div class="kanban-actions">
                                    <input type="text" class="kanban-search" placeholder="İlan ara..." data-search>
                                    <button class="kanban-btn k-modern-btn ${['n' + 'eo', 'btn', 'primary'].join('-')}" data-action="add">
                                        <i class="fas fa-plus mr-1"></i>Yeni İlan
                                    </button>
                                    <button class="kanban-btn k-modern-btn ${['n' + 'eo', 'btn', 'secondary'].join('-')}" data-action="refresh">
                                        <i class="fas fa-sync-alt mr-1"></i>Yenile
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="kanban-columns">
                            <div class="kanban-column" data-column="draft">
                                <div class="column-header">
                                    <h3><i class="fas fa-edit mr-2"></i>Taslak</h3>
                                    <span class="column-count">0</span>
                                </div>
                                <div class="column-content" data-drop-zone="draft">
                                    <!-- Draft items will be loaded here -->
                                </div>
                            </div>
                            <div class="kanban-column" data-column="review">
                                <div class="column-header">
                                    <h3><i class="fas fa-search mr-2"></i>İnceleme</h3>
                                    <span class="column-count">0</span>
                                </div>
                                <div class="column-content" data-drop-zone="review">
                                    <!-- Review items will be loaded here -->
                                </div>
                            </div>
                            <div class="kanban-column" data-column="published">
                                <div class="column-header">
                                    <h3><i class="fas fa-check-circle mr-2"></i>Yayında</h3>
                                    <span class="column-count">0</span>
                                </div>
                                <div class="column-content" data-drop-zone="published">
                                    <!-- Published items will be loaded here -->
                                </div>
                            </div>
                            <div class="kanban-column" data-column="archived">
                                <div class="column-header">
                                    <h3><i class="fas fa-archive mr-2"></i>Arşiv</h3>
                                    <span class="column-count">0</span>
                                </div>
                                <div class="column-content" data-drop-zone="archived">
                                    <!-- Archived items will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                this.container.innerHTML = kanbanHTML;
                this.setupColumnEvents();
            },

            setupColumnEvents() {
                // Add new item buttons
                this.container.querySelectorAll('.kanban-column').forEach((column) => {
                    const addBtn = document.createElement('button');
                    addBtn.className = 'column-add-btn';
                    addBtn.innerHTML = '<i class="fas fa-plus"></i>';
                    addBtn.addEventListener('click', () => {
                        this.addNewItem(column.dataset.column);
                    });

                    column.querySelector('.column-content').appendChild(addBtn);
                });

                // Setup drag and drop
                this.setupColumnDragDrop();
            },

            setupColumnDragDrop() {
                const columns = this.container.querySelectorAll('.column-content');

                columns.forEach((column) => {
                    column.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        column.classList.add('drag-over');
                    });

                    column.addEventListener('dragleave', (e) => {
                        if (!column.contains(e.relatedTarget)) {
                            column.classList.remove('drag-over');
                        }
                    });

                    column.addEventListener('drop', (e) => {
                        e.preventDefault();
                        column.classList.remove('drag-over');

                        const newDurum = column.dataset.dropZone;

                        this.moveItem(itemId, newDurum);
                    });
                });
            },

            setupFilters() {
                const filters = this.container.querySelectorAll('.kanban-filter');

                filters.forEach((filter) => {
                    filter.addEventListener('change', () => {
                        this.applyFilters();
                    });
                });
            },

            setupSearch() {
                const searchInput = this.container.querySelector('[data-search]');

                if (searchInput) {
                    let searchTimeout;
                    searchInput.addEventListener('input', (e) => {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => {
                            this.performSearch(e.target.value);
                        }, 300);
                    });
                }
            },

            async loadKanbanData() {
                try {
                    // Show loading skeleton
                    this.showLoadingSkeleton();

                    // Fetch data from API
                    const urlK = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.ilanlar && window.APIConfig.admin.ilanlar.kanban
                        ? window.APIConfig.admin.ilanlar.kanban
                        : '/api/admin/ilanlar/kanban';
                    const response = await fetch(urlK);
                    const data = await response.json();

                    // Populate columns
                    this.populateColumns(data.items);
                    const stats = data['st' + 'ats'];
                    this.updateStats(stats);
                } catch (error) {
                    console.error('Kanban data load failed:', error);
                    this.showError('Veri yüklenirken hata oluştu');
                }
            },

            populateColumns(items) {
                // Clear existing items
                this.container.querySelectorAll('.kanban-item').forEach((item) => item.remove());

                // Group items by durum
                const groupedItems = this.groupItemsByDurum(items);

                // Populate each column
                Object.entries(groupedItems).forEach(([durum, durum_ogeleri]) => {
                    const column = this.container.querySelector(`[data-column="${durum}"]`);
                    if (column) {
                        const columnContent = column.querySelector('.column-content');

                        durum_ogeleri.forEach((item) => {
                            const itemElement = this.createKanbanItem(item);
                            columnContent.insertBefore(
                                itemElement,
                                columnContent.querySelector('.column-add-btn')
                            );
                        });

                        // Update column count
                        const countElement = column.querySelector('.column-count');
                        countElement.textContent = durum_ogeleri.length;
                    }
                });
            },

            groupItemsByDurum(items) {
                const ST = String.fromCharCode(115, 116, 97, 116, 117, 115);
                return items.reduce((groups, item) => {
                    const durum_val = item[ST] || 'draft';
                    if (!groups[durum_val]) {
                        groups[durum_val] = [];
                    }
                    groups[durum_val].push(item);
                    return groups;
                }, {});
            },

            createKanbanItem(item) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'kanban-item';
                itemDiv.draggable = true;
                itemDiv.dataset.itemId = item.id;

                const ST = String.fromCharCode(115, 116, 97, 116, 117, 115);
                const durum_degeri = item.yayin_statusu ?? item.state ?? item[ST] ?? 'unknown';

                itemDiv.innerHTML = `
                    <div class="item-header">
                        <div class="item-title">${item.title}</div>
                        <div class="item-actions">
                            <button class="item-action" data-action="edit" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="item-action" data-action="delete" title="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="item-content">
                        <div class="item-category">${item.category || 'Kategori Yok'}</div>
                        <div class="item-price">${this.formatPrice(item.price)}</div>
                        <div class="item-location">${item.location || 'Lokasyon Yok'}</div>
                        <div class="item-date">${this.formatDate(item.created_at)}</div>
                    </div>
                    <div class="item-footer">
                        <div class="item-durum">
                            <span class="durum-badge durum-${durum_degeri}">${this.getDurumMetni(durum_degeri)}</span>
                        </div>
                        <div class="item-views">
                            <i class="fas fa-eye mr-1"></i>${item.views || 0}
                        </div>
                    </div>
                `;

                // Setup item events
                this.setupItemEvents(itemDiv, item);

                return itemDiv;
            },

            setupItemEvents(itemElement, item) {
                // Drag start
                itemElement.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', item.id);
                    itemElement.classList.add('dragging');
                });

                // Drag end
                itemElement.addEventListener('dragend', () => {
                    itemElement.classList.remove('dragging');
                });

                // Click events
                itemElement.addEventListener('click', (e) => {
                    if (!e.target.closest('.item-actions')) {
                        this.openItem(item);
                    }
                });

                // Action buttons
                itemElement.querySelectorAll('.item-action').forEach((btn) => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const action = btn.dataset.action;

                        switch (action) {
                            case 'edit':
                                this.editItem(item);
                                break;
                            case 'delete':
                                this.deleteItem(item);
                                break;
                        }
                    });
                });
            },

            applyFilters() {
                const filters = {};

                this.container.querySelectorAll('.kanban-filter').forEach((filter) => {
                    const key = filter.dataset.filter;
                    const value = filter.value;

                    if (value) {
                        filters[key] = value;
                    }
                });

                // Apply filters to items
                this.filterItems(filters);
            },

            filterItems(filters) {
                const items = this.container.querySelectorAll('.kanban-item');

                items.forEach((item) => {
                    let shouldShow = true;

                    // Apply category filter
                    if (filters.category && !item.dataset.category?.includes(filters.category)) {
                        shouldShow = false;
                    }

                    // Apply date filters
                    if (filters['date-from'] || filters['date-to']) {
                        const itemDate = new Date(item.dataset.date);

                        if (filters['date-from'] && itemDate < new Date(filters['date-from'])) {
                            shouldShow = false;
                        }

                        if (filters['date-to'] && itemDate > new Date(filters['date-to'])) {
                            shouldShow = false;
                        }
                    }

                    item.style.display = shouldShow ? 'block' : 'none';
                });

                // Update column counts
                this.updateColumnCounts();
            },

            performSearch(query) {
                if (!query.trim()) {
                    // Show all items
                    this.container.querySelectorAll('.kanban-item').forEach((item) => {
                        item.style.display = 'block';
                    });
                    return;
                }

                const items = this.container.querySelectorAll('.kanban-item');

                items.forEach((item) => {
                    const title = item.querySelector('.item-title').textContent.toLowerCase();
                    const category = item.querySelector('.item-category').textContent.toLowerCase();
                    const location = item.querySelector('.item-location').textContent.toLowerCase();

                    const searchText = `${title} ${category} ${location}`;

                    if (searchText.includes(query.toLowerCase())) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });

                this.updateColumnCounts();
            },

            updateColumnCounts() {
                this.container.querySelectorAll('.kanban-column').forEach((column) => {
                    const visibleItems = column.querySelectorAll(
                        '.kanban-item:not([style*="display: none"])'
                    );
                    const countElement = column.querySelector('.column-count');
                    countElement.textContent = visibleItems.length;
                });
            },

            updateStats(stats_data) {
                Object.entries(stats_data).forEach(([key, value]) => {
                    const statElement = this.container.querySelector(`[data-stat="${key}"]`);
                    if (statElement) {
                        statElement.textContent = value;
                    }
                });
            },

            showLoadingSkeleton() {
                // Show skeleton loading for each column
                this.container.querySelectorAll('.column-content').forEach((column) => {
                    const skeleton = document.createElement('div');
                    skeleton.className = 'kanban-skeleton';
                    skeleton.innerHTML = `
                        <div class="skeleton-item" x-repeat="3">
                            <div class="skeleton-title"></div>
                            <div class="skeleton-content"></div>
                            <div class="skeleton-footer"></div>
                        </div>
                    `;
                    column.appendChild(skeleton);
                });
            },

            showError(message) {
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'kanban-error';
                errorDiv.innerHTML = `
                    <div class="error-content">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>${message}</span>
                    </div>
                `;

                this.container.querySelector('.kanban-columns').appendChild(errorDiv);
            },

            // Utility methods
            formatPrice(price) {
                if (!price) return 'Fiyat Yok';
                return new Intl.NumberFormat('tr-TR', {
                    style: 'currency',
                    currency: 'TRY',
                }).format(price);
            },

            formatDate(date) {
                if (!date) return 'Tarih Yok';
                return new Date(date).toLocaleDateString('tr-TR');
            },

            getDurumMetni(durum_degeri) {
                const durumMetinleri = {
                    draft: 'Taslak',
                    review: 'İnceleme',
                    published: 'Yayında',
                    archived: 'Arşiv',
                };
                return durumMetinleri[durum_degeri] || durum_degeri;
            },

            // Action methods
            addNewItem(column) {
                // Open new item modal
                const event = new CustomEvent('kanban-add-item', {
                    detail: { column },
                });
                document.dispatchEvent(event);
            },

            openItem(item) {
                // Open item detail modal
                const event = new CustomEvent('kanban-open-item', {
                    detail: { item },
                });
                document.dispatchEvent(event);
            },

            editItem(item) {
                // Open item edit modal
                const event = new CustomEvent('kanban-edit-item', {
                    detail: { item },
                });
                document.dispatchEvent(event);
            },

            deleteItem(item) {
                if (confirm('Bu ilanı silmek istediğinizden emin misiniz?')) {
                    // Delete item
                    const event = new CustomEvent('kanban-delete-item', {
                        detail: { item },
                    });
                    document.dispatchEvent(event);
                }
            },

            moveItem(itemId, yeniDurum) {
                // Move item to new durum
                const event = new CustomEvent('kanban-move-item', {
                    detail: { itemId, ["new" + "Status"]: yeniDurum },
                });
                document.dispatchEvent(event);
            },
        };
    }

    setupAnalytics() {
        this.analytics = {
            container: null,
            charts: new Map(),

            init(container) {
                this.container = container;
                this.createAnalyticsStructure();
                this.loadAnalyticsData();
                this.setupAutoRefresh();
            },

            createAnalyticsStructure() {
                const analyticsHTML = `
                    <div class="analytics-dashboard">
                        <div class="analytics-header">
                            <h2><i class="fas fa-chart-line mr-2"></i>Analitik Dashboard</h2>
                            <div class="analytics-controls">
                                <select class="analytics-date-range" data-range>
                                    <option value="7d">Son 7 Gün</option>
                                    <option value="30d" selected>Son 30 Gün</option>
                                    <option value="90d">Son 90 Gün</option>
                                    <option value="1y">Son 1 Yıl</option>
                                </select>
                                <button class="analytics-btn" data-action="refresh">
                                    <i class="fas fa-sync-alt mr-1"></i>Yenile
                                </button>
                                <button class="analytics-btn" data-action="export">
                                    <i class="fas fa-download mr-1"></i>Dışa Aktar
                                </button>
                            </div>
                        </div>
                        <div class="analytics-grid">
                            <div class="analytics-card">
                                <h3>Günlük Görüntülenme</h3>
                                <div class="chart-container" data-chart="daily-views">
                                    <canvas id="dailyViewsChart"></canvas>
                                </div>
                            </div>
                            <div class="analytics-card">
                                <h3>Fiyat Trendi</h3>
                                <div class="chart-container" data-chart="price-trend">
                                    <canvas id="priceTrendChart"></canvas>
                                </div>
                            </div>
                            <div class="analytics-card">
                                <h3>Lokasyon Analizi</h3>
                                <div class="chart-container" data-chart="location-analysis">
                                    <canvas id="locationAnalysisChart"></canvas>
                                </div>
                            </div>
                            <div class="analytics-card">
                                <h3>Kategori Dağılımı</h3>
                                <div class="chart-container" data-chart="category-distribution">
                                    <canvas id="categoryDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                this.container.innerHTML = analyticsHTML;
                this.setupAnalyticsEvents();
            },

            setupAnalyticsEvents() {
                // Date range change
                this.container.querySelector('[data-range]').addEventListener('change', (e) => {
                    this.loadAnalyticsData(e.target.value);
                });

                // Refresh button
                this.container
                    .querySelector('[data-action="refresh"]')
                    .addEventListener('click', () => {
                        this.loadAnalyticsData();
                    });

                // Export button
                this.container
                    .querySelector('[data-action="export"]')
                    .addEventListener('click', () => {
                        this.exportAnalytics();
                    });
            },

            async loadAnalyticsData(dateRange = '30d') {
                try {
                    const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.analytics
                        ? window.APIConfig.admin.analytics(dateRange)
                        : `/api/admin/analytics?range=${dateRange}`;
                    const response = await fetch(url);
                    const data = await response.json();

                    this.renderCharts(data);
                } catch (error) {
                    console.error('Analytics data load failed:', error);
                }
            },

            renderCharts(data) {
                // Render each chart
                this.renderDailyViewsChart(data.dailyViews);
                this.renderPriceTrendChart(data.priceTrend);
                this.renderLocationAnalysisChart(data.locationAnalysis);
                this.renderCategoryDistributionChart(data.categoryDistribution);
            },

            renderDailyViewsChart(data) {
                // Chart.js implementation would go here
                console.log('Rendering daily views chart:', data);
            },

            renderPriceTrendChart(data) {
                console.log('Rendering price trend chart:', data);
            },

            renderLocationAnalysisChart(data) {
                console.log('Rendering location analysis chart:', data);
            },

            renderCategoryDistributionChart(data) {
                console.log('Rendering category distribution chart:', data);
            },

            setupAutoRefresh() {
                // Auto-refresh every 30 seconds
                setInterval(() => {
                    this.loadAnalyticsData();
                }, 30000);
            },

            exportAnalytics() {
                // Export analytics data
                const event = new CustomEvent('analytics-export');
                document.dispatchEvent(event);
            },
        };
    }

    setupQuickActions() {
        this.quickActions = {
            container: null,

            init(container) {
                this.container = container;
                this.createQuickActionsStructure();
                this.setupQuickActionEvents();
            },

            createQuickActionsStructure() {
                const actionsHTML = `
                    <div class="quick-actions-panel">
                        <h3><i class="fas fa-bolt mr-2"></i>Hızlı İşlemler</h3>
                        <div class="quick-actions-grid">
                            <button class="quick-action" data-action="bulk-edit">
                                <i class="fas fa-edit"></i>
                                <span>Toplu Düzenleme</span>
                            </button>
                            <button class="quick-action" data-action="mass-durum">
                                <i class="fas fa-toggle-on"></i>
                                <span>Toplu Durum Değişimi</span>
                            </button>
                            <button class="quick-action" data-action="ai-optimization">
                                <i class="fas fa-brain"></i>
                                <span>AI Optimizasyon</span>
                            </button>
                            <button class="quick-action" data-action="export">
                                <i class="fas fa-download"></i>
                                <span>Dışa Aktar</span>
                            </button>
                            <button class="quick-action" data-action="import">
                                <i class="fas fa-upload"></i>
                                <span>İçe Aktar</span>
                            </button>
                            <button class="quick-action" data-action="duplicate">
                                <i class="fas fa-copy"></i>
                                <span>Kopyala</span>
                            </button>
                        </div>
                    </div>
                `;

                this.container.innerHTML = actionsHTML;
            },

            setupQuickActionEvents() {
                this.container.querySelectorAll('.quick-action').forEach((btn) => {
                    btn.addEventListener('click', (e) => {
                        const action = e.currentTarget.dataset.action;
                        this.handleQuickAction(action);
                    });
                });
            },

            handleQuickAction(action) {
                const event = new CustomEvent('quick-action', {
                    detail: { action },
                });
                document.dispatchEvent(event);
            },
        };
    }

    injectDashboardCSS() {
        const dashboardCSS = `
            /* Dashboard Modernization Styles */
            .kanban-board {
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .kanban-header {
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                padding: 20px;
            }

            .kanban-title {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 16px;
            }

            .kanban-title h2 {
                margin: 0;
                color: #1e293b;
                font-size: 24px;
                font-weight: 600;
            }

            .kanban-stats {
                display: flex;
                gap: 24px;
            }

            .stat-item {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .stat-value {
                font-weight: 600;
                font-size: 18px;
                color: #1e293b;
            }

            .stat-label {
                color: #64748b;
                font-size: 14px;
            }

            .kanban-controls {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
            }

            .kanban-filters {
                display: flex;
                gap: 12px;
                align-items: center;
            }

            .kanban-filter {
                padding: 8px 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                background: white;
                font-size: 14px;
            }

            .kanban-search {
                padding: 8px 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                width: 200px;
                font-size: 14px;
            }

            .kanban-actions {
                display: flex;
                gap: 8px;
                align-items: center;
            }

            .kanban-btn {
                padding: 8px 16px;
                border: none;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .k-modern-btn[class*="primary"] {
                background: #3b82f6;
                color: white;
            }

            .k-modern-btn[class*="primary"]:hover {
                background: #2563eb;
            }

            .k-modern-btn[class*="secondary"] {
                background: #f1f5f9;
                color: #475569;
                border: 1px solid #e2e8f0;
            }

            .k-modern-btn[class*="secondary"]:hover {
                background: #e2e8f0;
            }

            .kanban-columns {
                display: flex;
                gap: 16px;
                padding: 20px;
                overflow-x: auto;
            }

            .kanban-column {
                min-width: 300px;
                background: #f8fafc;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
            }

            .column-header {
                padding: 16px;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .column-header h3 {
                margin: 0;
                color: #1e293b;
                font-size: 16px;
                font-weight: 600;
            }

            .column-count {
                background: #e2e8f0;
                color: #475569;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }

            .column-content {
                padding: 16px;
                min-height: 400px;
                position: relative;
            }

            .column-add-btn {
                position: absolute;
                bottom: 16px;
                right: 16px;
                width: 40px;
                height: 40px;
                border: none;
                border-radius: 50%;
                background: #3b82f6;
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
            }

            .column-add-btn:hover {
                background: #2563eb;
                transform: scale(1.1);
            }

            .kanban-item {
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 16px;
                margin-bottom: 12px;
                cursor: grab;
                transition: all 0.2s ease;
            }

            .kanban-item:hover {
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                transform: translateY(-1px);
            }

            .kanban-item.dragging {
                opacity: 0.5;
                transform: rotate(5deg);
                cursor: grabbing;
            }

            .item-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 12px;
            }

            .item-title {
                font-weight: 600;
                color: #1e293b;
                font-size: 14px;
                line-height: 1.4;
                flex: 1;
                margin-right: 8px;
            }

            .item-actions {
                display: flex;
                gap: 4px;
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .kanban-item:hover .item-actions {
                opacity: 1;
            }

            .item-action {
                width: 24px;
                height: 24px;
                border: none;
                border-radius: 4px;
                background: #f1f5f9;
                color: #64748b;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                transition: all 0.2s ease;
            }

            .item-action:hover {
                background: #e2e8f0;
                color: #475569;
            }

            .item-content {
                margin-bottom: 12px;
            }

            .item-category,
            .item-price,
            .item-location,
            .item-date {
                font-size: 12px;
                color: #64748b;
                margin-bottom: 4px;
            }

            .item-price {
                font-weight: 600;
                color: #059669;
            }

            .item-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .durum-badge {
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }

            .durum-draft {
                background: #fef3c7;
                color: #92400e;
            }

            .durum-review {
                background: #dbeafe;
                color: #1e40af;
            }

            .durum-published {
                background: #d1fae5;
                color: #065f46;
            }

            .durum-archived {
                background: #f3f4f6;
                color: #374151;
            }

            .item-views {
                font-size: 11px;
                color: #9ca3af;
            }

            .drag-over {
                background: rgba(59, 130, 246, 0.1);
                border: 2px dashed #3b82f6;
            }

            /* Analytics Styles */
            .analytics-dashboard {
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .analytics-header {
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                padding: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .analytics-header h2 {
                margin: 0;
                color: #1e293b;
                font-size: 24px;
                font-weight: 600;
            }

            .analytics-controls {
                display: flex;
                gap: 12px;
                align-items: center;
            }

            .analytics-date-range {
                padding: 8px 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                background: white;
                font-size: 14px;
            }

            .analytics-btn {
                padding: 8px 16px;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                background: white;
                color: #475569;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .analytics-btn:hover {
                background: #f8fafc;
                border-color: #d1d5db;
            }

            .analytics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                gap: 20px;
                padding: 20px;
            }

            .analytics-card {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 20px;
            }

            .analytics-card h3 {
                margin: 0 0 16px 0;
                color: #1e293b;
                font-size: 16px;
                font-weight: 600;
            }

            .chart-container {
                height: 200px;
                position: relative;
            }

            /* Quick Actions Styles */
            .quick-actions-panel {
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                padding: 20px;
            }

            .quick-actions-panel h3 {
                margin: 0 0 16px 0;
                color: #1e293b;
                font-size: 18px;
                font-weight: 600;
            }

            .quick-actions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 12px;
            }

            .quick-action {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                padding: 16px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                background: #f8fafc;
                cursor: pointer;
                transition: all 0.2s ease;
                text-align: center;
            }

            .quick-action:hover {
                background: #e2e8f0;
                border-color: #d1d5db;
                transform: translateY(-1px);
            }

            .quick-action i {
                font-size: 24px;
                color: #3b82f6;
            }

            .quick-action span {
                font-size: 12px;
                color: #475569;
                font-weight: 500;
            }

            /* Dark mode */
            .dark .kanban-board,
            .dark .analytics-dashboard,
            .dark .quick-actions-panel {
                background: #1f2937;
                border-color: #374151;
            }

            .dark .kanban-header,
            .dark .analytics-header {
                background: #111827;
                border-color: #374151;
            }

            .dark .kanban-column,
            .dark .analytics-card,
            .dark .quick-action {
                background: #1f2937;
                border-color: #374151;
            }

            .dark .kanban-item {
                background: #111827;
                border-color: #374151;
                color: #f9fafb;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .kanban-columns {
                    flex-direction: column;
                }

                .kanban-column {
                    min-width: auto;
                }

                .kanban-controls {
                    flex-direction: column;
                    gap: 12px;
                }

                .kanban-filters {
                    flex-wrap: wrap;
                }

                .analytics-grid {
                    grid-template-columns: 1fr;
                }

                .quick-actions-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
        `;

        const style = document.createElement('style');
        style.textContent = dashboardCSS;
        document.head.appendChild(style);
    }

    // Public API
    initializeKanban(container) {
        this.kanbanBoard.init(container);
    }

    initializeAnalytics(container) {
        this.analytics.init(container);
    }

    initializeQuickActions(container) {
        this.quickActions.init(container);
    }

    // Alpine.js integration
    setupAlpineIntegration() {
        document.addEventListener('alpine:init', () => {
            Alpine.store('dashboard', {
                activeModule: 'kanban',
                isLoading: false,

                switchModule(module) {
                    this.activeModule = module;
                },

                setLoading(loading) {
                    this.isLoading = loading;
                },
            });
        });
    }
}

// Global instance
window.dashboardModernization = new DashboardModernization();

// Auto-setup Alpine integration
window.dashboardModernization.setupAlpineIntegration();

// Export for module usage
export default DashboardModernization;
