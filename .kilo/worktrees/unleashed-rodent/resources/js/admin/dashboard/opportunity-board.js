(function () {
    'use strict';

    const SELECTORS = {
        container: 'ob-container',
        loading: 'ob-loading',
        refreshBtn: 'ob-refresh',
        badge: 'ob-badge',
        status: 'ob-durumu',
        template: 'ob-item-template',
    };

    class OpportunityBoardWidget {
        constructor() {
            this.container = document.getElementById(SELECTORS.container);
            if (!this.container) return; // Widget not present

            this.loading = document.getElementById(SELECTORS.loading);
            this.refreshBtn = document.getElementById(SELECTORS.refreshBtn);
            this.badge = document.getElementById(SELECTORS.badge);
            this.status = document.getElementById(SELECTORS.status);
            this.template = document.getElementById(SELECTORS.template);

            this.init();
        }

        init() {
            this.fetchOpportunities();
            this.bindEvents();

            // Auto refresh every 5 minutes
            setInterval(() => this.fetchOpportunities(), 300000);
        }

        bindEvents() {
            if (this.refreshBtn) {
                this.refreshBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.fetchOpportunities();
                });
            }
        }

        async fetchOpportunities() {
            this.setLoading(true);

            try {
                const response = await fetch('/admin/intelligence/opportunities?limit=5', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const data = await response.json();

                if (data.success && data.data && data.data.opportunities) {
                    this.render(data.data.opportunities);
                    this.updateStatus('Güncel', 'text-gray-500');
                    if (this.badge) {
                        this.badge.textContent = data.data.opportunities.length;
                        this.badge.classList.remove('hidden');
                    }
                } else {
                    throw new Error('Invalid data format');
                }
            } catch (error) {
                console.error('Opportunity Board Error:', error);
                this.updateStatus('Hata oluştu', 'text-red-500');
                this.container.innerHTML = `<div class="p-4 text-center text-sm text-red-500 dark:text-red-400">Veriler yüklenemedi. <br><button onclick="window.location.reload()" class="underline mt-1">Sayfayı yenile</button></div>`;
            } finally {
                this.setLoading(false);
            }
        }

        setLoading(isLoading) {
            if (this.loading) {
                this.loading.classList.toggle('hidden', !isLoading);
            }
            if (this.refreshBtn) {
                this.refreshBtn.disabled = isLoading;
                this.refreshBtn.classList.toggle('opacity-50', isLoading);
            }
            if (isLoading && this.status) {
                this.status.textContent = 'Yükleniyor...';
            }
        }

        updateStatus(text, colorClass) {
            if (this.status) {
                this.status.textContent = text;
                this.status.className = `text-xs ${colorClass} dark:text-gray-400`;
            }
        }

        render(opportunities) {
            // Clear current content (except loading overlay)
            const loadingNode = this.loading;
            this.container.innerHTML = '';
            if (loadingNode) this.container.appendChild(loadingNode);

            if (opportunities.length === 0) {
                this.container.insertAdjacentHTML(
                    'beforeend',
                    `
                    <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                        <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-slate-500">Şu an acil fırsat bulunmuyor.</p>
                    </div>
                `
                );
                return;
            }

            opportunities.forEach((opp) => {
                const clone = this.template.content.cloneNode(true);
                const item = clone.querySelector('div'); // Root div

                // Priority styling
                const priorityConfig = this.getPriorityConfig(opp.priority_level);

                item.classList.add(priorityConfig.bgClass, 'dark:bg-opacity-10');
                item.querySelector('.priority-stripe').classList.add(priorityConfig.stripeClass);

                const badge = item.querySelector('.priority-badge');
                badge.textContent = opp.priority_level;
                badge.classList.add(priorityConfig.badgeClass);

                // Content
                item.querySelector('.customer-name').textContent = opp.kisi_adi;
                item.querySelector('.request-title').textContent =
                    opp.talep_baslik || 'Genel Talep';

                // Scores
                item.querySelector('.match-score').textContent = `${Math.round(opp.match_score)}%`;
                item.querySelector('.churn-score').textContent = `${Math.round(opp.churn_risk)}%`;
                item.querySelector('.action-score').textContent = Math.round(opp.action_score);

                // Action
                item.onclick = (e) => {
                    // Prevent redirect if clicking specific buttons if necessary,
                    // but here the whole card is clickable.
                    window.location.href = `/admin/kisiler/${opp.kisi_id}?from=opportunity_board`;
                };

                // Quick Action Button
                const btn = item.querySelector('.quick-action-btn');
                btn.onclick = (e) => {
                    e.stopPropagation();
                    window.location.href = `/admin/kisiler/${opp.kisi_id}?action=call&from=opportunity_board`;
                };

                // Listen Button
                const listenBtn = item.querySelector('.listen-btn');
                if (listenBtn) {
                    listenBtn.onclick = (e) => {
                        e.stopPropagation();
                        this.playOpportunityAudio(opp.kisi_id, listenBtn);
                    };
                }

                this.container.appendChild(clone);
            });
        }

        async playOpportunityAudio(id, btn) {
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML =
                '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

            try {
                const response = await fetch(`/api/v1/ai/opportunity/${id}/audio`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (data.success && data.data.audio_url) {
                    const audio = new Audio(data.data.audio_url);
                    audio.play();

                    // Visual feedback while playing
                    btn.innerHTML =
                        '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>';

                    audio.onended = () => {
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    };
                } else {
                    throw new Error(data.message || 'Ses dosyası alınamadı');
                }
            } catch (error) {
                console.error('Audio Playback Error:', error);
                btn.innerHTML =
                    '<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }, 2000);
            }
        }

        getPriorityConfig(level) {
            switch (level) {
                case 'ACIL':
                    return {
                        bgClass: 'bg-red-50',
                        stripeClass: 'bg-red-500',
                        badgeClass: 'bg-red-500 text-white',
                    };
                case 'YÜKSEK':
                    return {
                        bgClass: 'bg-orange-50',
                        stripeClass: 'bg-orange-500',
                        badgeClass: 'bg-orange-500 text-white',
                    };
                case 'ORTA':
                    return {
                        bgClass: 'bg-yellow-50',
                        stripeClass: 'bg-yellow-500',
                        badgeClass: 'bg-yellow-500 text-white',
                    };
                default:
                    return {
                        bgClass: 'bg-gray-50 dark:bg-slate-900',
                        stripeClass: 'bg-gray-400',
                        badgeClass: 'bg-gray-500 text-white',
                    };
            }
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new OpportunityBoardWidget());
    } else {
        new OpportunityBoardWidget();
    }
})();
