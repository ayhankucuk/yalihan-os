/**
 * Owner Portal Interface
 * Customer-facing self-service portal
 * Modern status tracking & inquiry management
 */

class OwnerPortal {
    constructor(options = {}) {
        this.container = options.container || '#owner-portal';
        this.apiEndpoint = options.apiEndpoint || (window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.owner && window.APIConfig.admin.owner.dashboard ? window.APIConfig.admin.owner.dashboard : '/api/owner/dashboard');
        this.ownerId = options.ownerId || null;
        this.refreshInterval = options.refreshInterval || 60000; // 1 minute

        this.refreshTimer = null;
        this.notifications = [];
        this.currentView = 'dashboard';

        this.init();
    }

    init() {
        this.createPortalInterface();
        this.attachStyles();
        this.loadPortalData();
        this.bindEvents();
        this.startAutoRefresh();
        this.initializeNotifications();
    }

    createPortalInterface() {
        const container = document.querySelector(this.container);
        if (!container) return;

        container.innerHTML = `
            <div class="neo-owner-portal">
                <!-- Portal Header -->
                <div class="neo-portal-header">
                    <div class="neo-header-content">
                        <div class="neo-brand">
                            <div class="neo-brand-logo">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/>
                                    <polyline points="9,22 9,12 15,12 15,22" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                            <div class="neo-brand-text">
                                <h1 class="neo-brand-title">Ev Sahibi Portalı</h1>
                                <p class="neo-brand-subtitle">İlanlarınızı takip edin</p>
                            </div>
                        </div>
                        <div class="neo-header-actions">
                            <button class="neo-notification-btn" id="notifications-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="2"/>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <span class="neo-notification-badge" id="notification-count">0</span>
                            </button>
                            <div class="neo-owner-profile" id="owner-profile">
                                <div class="neo-owner-avatar">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                                <span class="neo-owner-name" id="owner-name">Ev Sahibi</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="neo-portal-nav">
                    <div class="neo-nav-container">
                        <button class="neo-nav-tab active" data-view="dashboard">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                                <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                                <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                                <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Dashboard
                        </button>
                        <button class="neo-nav-tab" data-view="listings">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            İlanlarım
                        </button>
                        <button class="neo-nav-tab" data-view="inquiries">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Sorular
                        </button>
                        <button class="neo-nav-tab" data-view="appointments">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/>
                                <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/>
                                <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Randevular
                        </button>
                    </div>
                </div>

                <!-- Portal Content -->
                <div class="neo-portal-content">
                    <!-- Dashboard View -->
                    <div class="neo-view neo-dashboard-view active" id="dashboard-view">
                        <!-- Overview Cards -->
                        <div class="neo-overview-grid">
                            <div class="neo-overview-card neo-card-primary">
                                <div class="neo-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                                <div class="neo-card-content">
                                    <div class="neo-card-value" id="total-listings">0</div>
                                    <div class="neo-card-label">Toplam İlan</div>
                                    <div class="neo-card-status active">
                                        <span class="neo-status-dot"></span>
                                        <span id="active-listings">0</span> Aktif
                                    </div>
                                </div>
                            </div>

                            <div class="neo-overview-card neo-card-success">
                                <div class="neo-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M22 12h-4l-3 9L9 3l-3 9H2" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                                <div class="neo-card-content">
                                    <div class="neo-card-value" id="total-views">0</div>
                                    <div class="neo-card-label">Toplam Görüntüleme</div>
                                    <div class="neo-card-trend">
                                        <span class="neo-trend-icon">↗</span>
                                        <span id="views-change">+0%</span> Bu hafta
                                    </div>
                                </div>
                            </div>

                            <div class="neo-overview-card neo-card-warning">
                                <div class="neo-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                                <div class="neo-card-content">
                                    <div class="neo-card-value" id="total-inquiries">0</div>
                                    <div class="neo-card-label">Yeni Sorular</div>
                                    <div class="neo-card-action">
                                        <button class="neo-btn-sm neo-btn-ghost" onclick="this.switchView('inquiries')">
                                            Görüntüle
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity Feed -->
                        <div class="neo-activity-section">
                            <div class="neo-section-header">
                                <h2 class="neo-section-title">Son Aktiviteler</h2>
                                <button class="neo-btn neo-btn-sm neo-neo-btn neo-btn-secondary" id="refresh-activity">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" stroke="currentColor" stroke-width="2"/>
                                        <path d="M21 3v5h-5" stroke="currentColor" stroke-width="2"/>
                                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" stroke="currentColor" stroke-width="2"/>
                                        <path d="M8 16H3v5" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    Yenile
                                </button>
                            </div>
                            <div class="neo-activity-feed" id="activity-feed">
                                <!-- Activity items will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- Listings View -->
                    <div class="neo-view neo-listings-view" id="listings-view">
                        <div class="neo-section-header">
                            <h2 class="neo-section-title">İlanlarım</h2>
                            <div class="neo-section-actions">
                                <div class="neo-filter-group">
                                    <select class="neo-filter-select" id="listing-status-filter">
                                        <option value="all">Tüm İlanlar</option>
                                        <option value="active">Aktif</option>
                                        <option value="pending">Beklemede</option>
                                        <option value="sold">Satıldı</option>
                                    </select>
                                </div>
                                <button class="neo-btn neo-neo-btn neo-btn-primary" id="add-listing-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 5v14m7-7H5" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    Yeni İlan
                                </button>
                            </div>
                        </div>
                        <div class="neo-listings-grid" id="listings-grid">
                            <!-- Listing cards will be populated here -->
                        </div>
                    </div>

                    <!-- Inquiries View -->
                    <div class="neo-view neo-inquiries-view" id="inquiries-view">
                        <div class="neo-section-header">
                            <h2 class="neo-section-title">Sorular & Talepler</h2>
                            <div class="neo-inquiry-stats">
                                <span class="neo-stat-item">
                                    <span class="neo-stat-value" id="unread-inquiries">0</span>
                                    <span class="neo-stat-label">Okunmamış</span>
                                </span>
                                <span class="neo-stat-item">
                                    <span class="neo-stat-value" id="today-inquiries">0</span>
                                    <span class="neo-stat-label">Bugün</span>
                                </span>
                            </div>
                        </div>
                        <div class="neo-inquiries-list" id="inquiries-list">
                            <!-- Inquiry items will be populated here -->
                        </div>
                    </div>

                    <!-- Appointments View -->
                    <div class="neo-view neo-appointments-view" id="appointments-view">
                        <div class="neo-section-header">
                            <h2 class="neo-section-title">Randevular</h2>
                            <button class="neo-btn neo-neo-btn neo-btn-primary" id="schedule-appointment">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 5v14m7-7H5" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Randevu Planla
                            </button>
                        </div>
                        <div class="neo-calendar-container">
                            <div class="neo-calendar-header">
                                <button class="neo-calendar-nav" id="prev-month">‹</button>
                                <h3 class="neo-calendar-title" id="calendar-title">Ekim 2025</h3>
                                <button class="neo-calendar-nav" id="next-month">›</button>
                            </div>
                            <div class="neo-calendar-grid" id="calendar-grid">
                                <!-- Calendar will be populated here -->
                            </div>
                        </div>
                        <div class="neo-appointments-list" id="appointments-list">
                            <!-- Appointment items will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Notifications Panel -->
                <div class="neo-notifications-panel" id="notifications-panel">
                    <div class="neo-notifications-header">
                        <h3 class="neo-notifications-title">Bildirimler</h3>
                        <button class="neo-notifications-close" id="close-notifications">×</button>
                    </div>
                    <div class="neo-notifications-list" id="notifications-list">
                        <!-- Notifications will be populated here -->
                    </div>
                </div>

                <!-- Loading States -->
                <div class="neo-loading-skeleton" id="loading-skeleton">
                    <div class="neo-skeleton-header"></div>
                    <div class="neo-skeleton-grid">
                        <div class="neo-skeleton-card"></div>
                        <div class="neo-skeleton-card"></div>
                        <div class="neo-skeleton-card"></div>
                    </div>
                </div>
            </div>
        `;
    }

    attachStyles() {
        const styles = `
            <style>
            :root {
                --neo-primary: #3b82f6;
                --neo-success: #10b981;
                --neo-warning: #f59e0b;
                --neo-danger: #ef4444;
                --neo-info: #8b5cf6;
                --neo-gray-50: #f9fafb;
                --neo-gray-100: #f3f4f6;
                --neo-gray-200: #e5e7eb;
                --neo-gray-300: #d1d5db;
                --neo-gray-500: #6b7280;
                --neo-gray-700: #374151;
                --neo-gray-900: #111827;
            }

            .neo-owner-portal {
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                position: relative;
            }

            /* Portal Header */
            .neo-portal-header {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(20px);
                border-bottom: 1px solid rgba(255, 255, 255, 0.2);
                padding: 1rem 2rem;
            }

            .neo-header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                max-width: 1200px;
                margin: 0 auto;
            }

            .neo-brand {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .neo-brand-logo {
                width: 48px;
                height: 48px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
            }

            .neo-brand-title {
                font-size: 1.25rem;
                font-weight: 700;
                color: white;
                margin: 0;
            }

            .neo-brand-subtitle {
                font-size: 0.875rem;
                color: rgba(255, 255, 255, 0.8);
                margin: 0;
            }

            .neo-header-actions {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .neo-notification-btn {
                position: relative;
                padding: 0.75rem;
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 0.75rem;
                color: white;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .neo-notification-btn:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .neo-notification-badge {
                position: absolute;
                top: -2px;
                right: -2px;
                background: var(--neo-danger);
                color: white;
                font-size: 0.7rem;
                font-weight: 600;
                padding: 0.25rem 0.5rem;
                border-radius: 1rem;
                min-width: 18px;
                text-align: center;
            }

            .neo-owner-profile {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem 0.75rem;
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 0.75rem;
                color: white;
            }

            .neo-owner-avatar {
                width: 32px;
                height: 32px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 0.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Navigation */
            .neo-portal-nav {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                border-bottom: 1px solid var(--neo-gray-200);
                padding: 0 2rem;
            }

            .neo-nav-container {
                display: flex;
                max-width: 1200px;
                margin: 0 auto;
            }

            .neo-nav-tab {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 1rem 1.5rem;
                border: none;
                background: none;
                color: var(--neo-gray-500);
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
                border-bottom: 2px solid transparent;
            }

            .neo-nav-tab:hover {
                color: var(--neo-primary);
                background: rgba(59, 130, 246, 0.05);
            }

            .neo-nav-tab.active {
                color: var(--neo-primary);
                border-bottom-color: var(--neo-primary);
            }

            /* Portal Content */
            .neo-portal-content {
                background: var(--neo-gray-50);
                min-height: calc(100vh - 140px);
                padding: 2rem;
            }

            .neo-portal-content > * {
                max-width: 1200px;
                margin: 0 auto;
            }

            .neo-view {
                display: none;
            }

            .neo-view.active {
                display: block;
                animation: neo-fadeIn 0.3s ease-out;
            }

            @keyframes neo-fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* Overview Cards */
            .neo-overview-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }

            .neo-overview-card {
                background: white;
                border-radius: 1rem;
                padding: 1.5rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                border: 1px solid var(--neo-gray-100);
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .neo-overview-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: var(--card-color);
            }

            .neo-overview-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            }

            .neo-card-primary { --card-color: var(--neo-primary); }
            .neo-card-success { --card-color: var(--neo-success); }
            .neo-card-warning { --card-color: var(--neo-warning); }

            .neo-card-icon {
                width: 48px;
                height: 48px;
                border-radius: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--card-color);
                color: white;
            }

            .neo-card-content {
                flex: 1;
            }

            .neo-card-value {
                font-size: 1.875rem;
                font-weight: 700;
                color: var(--neo-gray-900);
                margin-bottom: 0.25rem;
            }

            .neo-card-label {
                font-size: 0.875rem;
                color: var(--neo-gray-500);
                margin-bottom: 0.5rem;
            }

            .neo-card-status {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.875rem;
                color: var(--neo-success);
            }

            .neo-status-dot {
                width: 8px;
                height: 8px;
                background: var(--neo-success);
                border-radius: 50%;
            }

            .neo-card-trend {
                display: flex;
                align-items: center;
                gap: 0.25rem;
                font-size: 0.875rem;
                color: var(--neo-success);
            }

            .neo-trend-icon {
                font-size: 1rem;
            }

            /* Section Headers */
            .neo-section-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.5rem;
            }

            .neo-section-title {
                font-size: 1.25rem;
                font-weight: 600;
                color: var(--neo-gray-900);
                margin: 0;
            }

            /* Activity Feed */
            .neo-activity-section {
                background: white;
                border-radius: 1rem;
                padding: 1.5rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                border: 1px solid var(--neo-gray-100);
            }

            .neo-activity-feed {
                max-height: 400px;
                overflow-y: auto;
            }

            .neo-activity-item {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem 0;
                border-bottom: 1px solid var(--neo-gray-100);
            }

            .neo-activity-item:last-child {
                border-bottom: none;
            }

            .neo-activity-icon {
                width: 32px;
                height: 32px;
                background: var(--neo-gray-100);
                border-radius: 0.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--neo-gray-500);
            }

            .neo-activity-content {
                flex: 1;
            }

            .neo-activity-title {
                font-size: 0.875rem;
                font-weight: 500;
                color: var(--neo-gray-900);
                margin: 0 0 0.25rem 0;
            }

            .neo-activity-time {
                font-size: 0.75rem;
                color: var(--neo-gray-500);
                margin: 0;
            }

            /* Buttons */
            .neo-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1.25rem;
                border: 1px solid transparent;
                border-radius: 0.75rem;
                font-size: 0.875rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
                text-decoration: none;
            }

            .neo-neo-btn neo-btn-primary {
                background: var(--neo-primary);
                color: white;
            }

            .neo-neo-btn neo-btn-primary:hover {
                background: #2563eb;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
            }

            .neo-neo-btn neo-btn-secondary {
                background: var(--neo-gray-100);
                color: var(--neo-gray-700);
            }

            .neo-neo-btn neo-btn-secondary:hover {
                background: var(--neo-gray-200);
            }

            .neo-btn-sm {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }

            .neo-btn-ghost {
                background: none;
                color: var(--neo-gray-500);
                border-color: var(--neo-gray-200);
            }

            .neo-btn-ghost:hover {
                background: var(--neo-gray-50);
                color: var(--neo-gray-700);
            }

            /* Loading Skeleton */
            .neo-loading-skeleton {
                display: none;
                padding: 2rem 0;
            }

            .neo-skeleton-header {
                height: 2rem;
                background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
                background-size: 200% 100%;
                animation: neo-shimmer 1.5s infinite;
                border-radius: 0.5rem;
                margin-bottom: 1.5rem;
                width: 200px;
            }

            .neo-skeleton-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1.5rem;
            }

            .neo-skeleton-card {
                height: 120px;
                background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
                background-size: 200% 100%;
                animation: neo-shimmer 1.5s infinite;
                border-radius: 1rem;
            }

            @keyframes neo-shimmer {
                0% { background-position: -200% 0; }
                100% { background-position: 200% 0; }
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .neo-portal-header,
                .neo-portal-nav,
                .neo-portal-content {
                    padding-left: 1rem;
                    padding-right: 1rem;
                }

                .neo-overview-grid {
                    grid-template-columns: 1fr;
                }

                .neo-section-header {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 1rem;
                }

                .neo-nav-container {
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }

                .neo-nav-tab {
                    white-space: nowrap;
                    padding: 1rem;
                }
            }
            </style>
        `;

        if (!document.querySelector('#neo-owner-portal-styles')) {
            const styleElement = document.createElement('div');
            styleElement.id = 'neo-owner-portal-styles';
            styleElement.innerHTML = styles;
            document.head.appendChild(styleElement);
        }
    }

    async loadPortalData() {
        try {
            this.showLoading();

            // ✅ API Helper kullan (merkezi yönetim)
            const endpoint = this.apiEndpoint + (this.ownerId ? `/${this.ownerId}` : '');
            let data;
            if (window.APIHelper) {
                const result = await window.APIHelper.request(endpoint, {
                    method: 'GET',
                }, {
                    showLoading: false, // Kendi loading'ini yönetiyor
                });
                data = result.data || result;
            } else {
                // Fallback: Eski kod
                const response = await fetch(endpoint, {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                data = await response.json();
            }

            if (data.success) {
                this.updateDashboard(data.data);
                this.updateActivity(data.data.activities);
                this.updateNotifications(data.data.notifications);
            } else {
                this.showError(data.message || 'Portal verileri yüklenemedi');
            }
        } catch (error) {
            console.error('Portal load error:', error);
            this.showError('Bağlantı hatası oluştu');
        } finally {
            this.hideLoading();
        }
    }

    updateDashboard(data) {
        // Update owner info
        if (data.owner) {
            document.getElementById('owner-name').textContent = data.owner.name;
        }

        // Update overview cards
        const overview = data.overview || {};
        document.getElementById('total-listings').textContent = overview.total_listings || 0;
        document.getElementById('active-listings').textContent = overview.active_listings || 0;
        document.getElementById('total-views').textContent = overview.total_views || 0;
        document.getElementById('total-inquiries').textContent = overview.total_inquiries || 0;
        document.getElementById('views-change').textContent = `+${overview.views_change || 0}%`;
    }

    updateActivity(activities) {
        const activityFeed = document.getElementById('activity-feed');
        if (!activityFeed || !activities) return;

        activityFeed.innerHTML = activities
            .map(
                (activity) => `
            <div class="neo-activity-item">
                <div class="neo-activity-icon">
                    ${this.getActivityIcon(activity.type)}
                </div>
                <div class="neo-activity-content">
                    <p class="neo-activity-title">${activity.title}</p>
                    <p class="neo-activity-time">${this.formatTime(activity.created_at)}</p>
                </div>
            </div>
        `
            )
            .join('');
    }

    bindEvents() {
        // Navigation tabs
        document.querySelectorAll('.neo-nav-tab').forEach((tab) => {
            tab.addEventListener('click', (e) => {
                const view = e.target.dataset.view;
                this.switchView(view);
            });
        });

        // Notifications
        document.getElementById('notifications-btn')?.addEventListener('click', () => {
            this.toggleNotifications();
        });

        document.getElementById('close-notifications')?.addEventListener('click', () => {
            this.closeNotifications();
        });

        // Refresh activity
        document.getElementById('refresh-activity')?.addEventListener('click', () => {
            this.loadPortalData();
        });
    }

    switchView(viewName) {
        // Update active tab
        document.querySelectorAll('.neo-nav-tab').forEach((tab) => {
            tab.classList.toggle('active', tab.dataset.view === viewName);
        });

        // Update active view
        document.querySelectorAll('.neo-view').forEach((view) => {
            view.classList.toggle('active', view.id === `${viewName}-view`);
        });

        this.currentView = viewName;

        // Load view-specific data
        if (viewName === 'listings') {
            this.loadListings();
        } else if (viewName === 'inquiries') {
            this.loadInquiries();
        } else if (viewName === 'appointments') {
            this.loadAppointments();
        }
    }

    getActivityIcon(type) {
        const icons = {
            listing_created:
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14m7-7H5" stroke="currentColor" stroke-width="2"/></svg>',
            inquiry_received:
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke="currentColor" stroke-width="2"/></svg>',
            appointment_scheduled:
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/></svg>',
            default:
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>',
        };
        return icons[type] || icons.default;
    }

    formatTime(timestamp) {
        return new Intl.RelativeTimeFormat('tr', { numeric: 'auto' }).format(
            Math.floor((new Date(timestamp) - new Date()) / (1000 * 60 * 60 * 24)),
            'day'
        );
    }

    showLoading() {
        document.getElementById('loading-skeleton').style.display = 'block';
        document.querySelectorAll('.neo-view').forEach((view) => {
            view.style.display = 'none';
        });
    }

    hideLoading() {
        document.getElementById('loading-skeleton').style.display = 'none';
        document.getElementById(`${this.currentView}-view`).style.display = 'block';
    }

    startAutoRefresh() {
        this.refreshTimer = setInterval(() => {
            this.loadPortalData();
        }, this.refreshInterval);
    }

    initializeNotifications() {
        // Initialize notification system
        this.updateNotificationBadge();
    }

    updateNotifications(notifications) {
        this.notifications = notifications || [];
        this.updateNotificationBadge();
    }

    updateNotificationBadge() {
        const badge = document.getElementById('notification-count');
        const unreadCount = this.notifications.filter((n) => !n.read).length;
        badge.textContent = unreadCount;
        badge.style.display = unreadCount > 0 ? 'block' : 'none';
    }

    toggleNotifications() {
        const panel = document.getElementById('notifications-panel');
        panel.classList.toggle('active');
    }

    closeNotifications() {
        document.getElementById('notifications-panel').classList.remove('active');
    }

    destroy() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }

        const container = document.querySelector(this.container);
        if (container) {
            container.innerHTML = '';
        }
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('#owner-portal')) {
        window.ownerPortal = new OwnerPortal({
            container: '#owner-portal',
        });
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OwnerPortal;
}
