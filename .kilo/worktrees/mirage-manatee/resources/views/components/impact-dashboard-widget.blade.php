<!-- Real-Time User Impact Widget -->
<!-- Bu widget tüm admin sayfalarında gösterilebilir -->

<div id="impact-dashboard-widget"
    class="fixed bottom-4 right-4 w-80 bg-white dark:bg-slate-900 rounded-lg shadow-xl border border-gray-200 dark:border-slate-800 z-50 transition-all duration-300 hover:scale-105 dark:border-slate-700"
    x-data="impactWidget()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
            <span class="animate-pulse text-green-500 mr-2">●</span>
            Impact Dashboard
        </h3>
        <button @click="collapsed = !collapsed"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200">
            <svg x-show="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
            <svg x-show="collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
            </svg>
        </button>
    </div>

    <!-- Content -->
    <div x-show="!collapsed" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100"
        class="p-4 space-y-4">

        <!-- Today's Impact Summary -->
        <div
            class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-3 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Today's Impact</span>
                <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold" x-text="lastUpdated"></span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="text-center">
                    <div class="text-xl font-bold text-green-600" x-text="metrics.performance_gain"></div>
                    <div class="text-xs text-gray-500">Performance ↗</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-purple-600" x-text="metrics.time_saved"></div>
                    <div class="text-xs text-gray-500">Time Saved</div>
                </div>
            </div>
        </div>

        <!-- Real-time Metrics -->
        <div class="space-y-3">
            <!-- Ideas Implementation Progress -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg dark:bg-slate-900">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">💡 Ideas Progress</span>
                    <span class="text-xs text-gray-500"
                        x-text="metrics.ideas_implemented + '/' + metrics.ideas_total"></span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                    <div class="bg-gradient-to-r from-yellow-400 to-orange-500 h-2 rounded-full transition-all duration-500"
                        :style="`width: ${(metrics.ideas_implemented / metrics.ideas_total) * 100}%`"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>Implementation Rate</span>
                    <span x-text="Math.round((metrics.ideas_implemented / metrics.ideas_total) * 100) + '%'"></span>
                </div>
            </div>

            <!-- Code Quality Score -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg dark:bg-slate-900">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">📊 Code Quality</span>
                    <div class="flex items-center space-x-1">
                        <span class="text-xs font-semibold"
                            :class="metrics.quality_trend === 'up' ? 'text-green-500' : metrics
                                .quality_trend === 'down' ? 'text-red-500' : 'text-gray-500'"
                            x-text="metrics.quality_score + '/100'"></span>
                        <span class="text-xs"
                            :class="metrics.quality_trend === 'up' ? 'text-green-500' : metrics
                                .quality_trend === 'down' ? 'text-red-500' : 'text-gray-500'">
                            <span x-show="metrics.quality_trend === 'up'">↗</span>
                            <span x-show="metrics.quality_trend === 'down'">↘</span>
                            <span x-show="metrics.quality_trend === 'stable'">→</span>
                        </span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                    <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full transition-all duration-500"
                        :style="`width: ${metrics.quality_score}%`"></div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg dark:bg-slate-900">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">⚡ Performance</span>
                    <span class="text-xs text-blue-600 dark:text-blue-400" x-text="metrics.avg_response_time"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <div class="text-lg font-bold text-blue-600" x-text="metrics.cache_hit_ratio"></div>
                        <div class="text-xs text-gray-500">Cache Hit</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-indigo-600" x-text="metrics.db_optimization"></div>
                        <div class="text-xs text-gray-500">DB Opt</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-purple-600" x-text="metrics.memory_usage"></div>
                        <div class="text-xs text-gray-500">Memory</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="border-t border-gray-200 dark:border-slate-800 pt-3 dark:border-slate-700">
            <div class="text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Recent Activities</div>
            <div class="space-y-2 max-h-24 overflow-y-auto">
                <template x-for="activity in recentActivities" :key="activity.id">
                    <div class="flex items-center space-x-2 text-xs">
                        <span class="w-2 h-2 rounded-full"
                            :class="activity.type === 'success' ? 'bg-green-500' : activity.type === 'improvement' ?
                                'bg-blue-500' : 'bg-yellow-500'"></span>
                        <span class="text-gray-600 dark:text-gray-400 flex-1" x-text="activity.message"></span>
                        <span class="text-gray-400" x-text="activity.time"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-2 gap-2 border-t border-gray-200 dark:border-slate-800 pt-3 dark:border-slate-700">
            <button @click="generateIdeas()"
                class="flex items-center justify-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                    </path>
                </svg>
                Ideas
            </button>
            <button @click="runCodeReview()"
                class="flex items-center justify-center px-3 py-2 bg-purple-500 hover:bg-purple-600 text-white text-xs rounded transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Review
            </button>
        </div>

        <!-- Business Value Indicator -->
        <div
            class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-3 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">💰 Monthly Value</div>
                    <div class="text-xl font-bold text-green-600" x-text="'$' + metrics.monthly_value"></div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">ROI</div>
                    <div class="text-xl font-bold text-green-600" x-text="metrics.roi + '%'"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function impactWidget() {
        return {
            collapsed: false,
            lastUpdated: '',
            metrics: {
                performance_gain: '+0%',
                time_saved: '0h',
                ideas_implemented: 0,
                ideas_total: 0,
                quality_score: 0,
                quality_trend: 'stable',
                cache_hit_ratio: '0%',
                db_optimization: '0%',
                memory_usage: '0MB',
                avg_response_time: '0ms',
                monthly_value: '0',
                roi: '0'
            },
            recentActivities: [],

            init() {
                this.updateLastUpdated();
                this.loadMetrics();

                // Auto refresh every 30 seconds
                setInterval(() => {
                    this.loadMetrics();
                    this.updateLastUpdated();
                }, 30000);

                // Listen for real-time updates
                this.setupWebSocket();
            },

            updateLastUpdated() {
                this.lastUpdated = new Date().toLocaleTimeString('tr-TR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            async loadMetrics() {
                try {
                    const response = await fetch('/admin/analytics/impact-metrics');
                    const data = await response.json();

                    if (data.success) {
                        this.metrics = {
                            ...this.metrics,
                            ...data.metrics
                        };
                        this.recentActivities = data.recent_activities || [];

                        // Show toast for significant improvements
                        if (data.new_improvement) {
                            this.showImpactToast(data.new_improvement);
                        }
                    }
                } catch (error) {
                    console.error('Failed to load impact metrics:', error);
                }
            },

            setupWebSocket() {
                if (typeof window.Echo !== 'undefined') {
                    window.Echo.channel('impact-updates')
                        .listen('ImpactMetricUpdated', (e) => {
                            this.updateMetric(e.metric, e.value);
                            this.addActivity({
                                id: Date.now(),
                                type: 'improvement',
                                message: `${e.metric} improved: ${e.value}`,
                                time: 'now'
                            });
                        });
                }
            },

            updateMetric(metric, value) {
                if (this.metrics.hasOwnProperty(metric)) {
                    this.metrics[metric] = value;
                }
            },

            addActivity(activity) {
                this.recentActivities.unshift(activity);
                if (this.recentActivities.length > 5) {
                    this.recentActivities.pop();
                }
            },

            showImpactToast(improvement) {
                if (window.toast) {
                    window.toast.success(`🚀 ${improvement.title}: ${improvement.value}`, {
                        duration: 5000,
                        position: 'bottom-left'
                    });
                }
            },

            async generateIdeas() {
                this.collapsed = true;

                // Show loading toast
                if (window.toast) {
                    window.toast.info('💡 Generating development ideas...', {
                        duration: 3000
                    });
                }

                try {
                    const response = await fetch('/admin/ai/generate-ideas', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            category: 'all',
                            priority: 'high'
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.toast.success(`✅ ${result.ideas_count} ideas generated!`);

                        // Update metrics
                        this.metrics.ideas_total += result.ideas_count;

                        // Add activity
                        this.addActivity({
                            id: Date.now(),
                            type: 'success',
                            message: `${result.ideas_count} new ideas generated`,
                            time: 'now'
                        });
                    } else {
                        window.toast.error('❌ Failed to generate ideas');
                    }
                } catch (error) {
                    window.toast.error('❌ Error generating ideas');
                    console.error(error);
                }
            },

            async runCodeReview() {
                this.collapsed = true;

                // Show loading toast
                if (window.toast) {
                    window.toast.info('🔍 Running AI code review...', {
                        duration: 3000
                    });
                }

                try {
                    const response = await fetch('/admin/ai/code-review', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            scope: 'recent',
                            fix: true
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.toast.success(`✅ Code review completed! ${result.issues_fixed} issues fixed`);

                        // Update quality score
                        this.metrics.quality_score = Math.min(100, this.metrics.quality_score + result
                            .quality_improvement);
                        this.metrics.quality_trend = 'up';

                        // Add activity
                        this.addActivity({
                            id: Date.now(),
                            type: 'success',
                            message: `Fixed ${result.issues_fixed} code issues`,
                            time: 'now'
                        });
                    } else {
                        window.toast.error('❌ Code review failed');
                    }
                } catch (error) {
                    window.toast.error('❌ Error running code review');
                    console.error(error);
                }
            }
        }
    }
</script>

<style>
    #impact-dashboard-widget {
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .dark #impact-dashboard-widget {
        background: rgba(31, 41, 55, 0.95);
        border: 1px solid rgba(75, 85, 99, 0.3);
    }

    /* Custom scrollbar for activities */
    #impact-dashboard-widget ::-webkit-scrollbar {
        width: 4px;
    }

    #impact-dashboard-widget ::-webkit-scrollbar-track {
        background: transparent;
    }

    #impact-dashboard-widget ::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.5);
        border-radius: 2px;
    }

    #impact-dashboard-widget ::-webkit-scrollbar-thumb:hover {
        background: rgba(156, 163, 175, 0.8);
    }
</style>
