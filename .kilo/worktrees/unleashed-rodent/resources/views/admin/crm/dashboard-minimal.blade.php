@extends('admin.layouts.admin')

@section('title', 'CRM Dashboard')

@section('content')
    <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">CRM Dashboard</h1>
                <p class="text-gray-600 dark:text-gray-400">Müşteri ilişkileri özeti</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.crm.customers.create') }}" class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                    <i class="fas fa-user-plus mr-2"></i>Yeni Müşteri
                </a>
                <a href="{{ route('admin.crm.customers.index') }}" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 touch-target-optimized dark:shadow-none">
                    <i class="fas fa-list mr-2"></i>Tüm Müşteriler
                </a>
            </div>
        </div>

        <!-- Quick Stats - 3 Column Layout -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                        {{ number_format($stats['total_customers']) }}
                    </div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Toplam Müşteri
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-2">
                        {{ number_format($stats['active_customers']) }}
                    </div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Aktif Müşteri
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div class="px-6 py-4 p-6 text-center">
                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mb-2">
                        {{ number_format($stats['pending_followups']) }}
                    </div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Bekleyen Takip
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content - 2 Column -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Recent Activities - Takes 2/3 -->
            <div class="xl:col-span-2">
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center dark:border-slate-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Son Aktiviteler</h3>
                        <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 text-xs font-medium rounded-full">{{ count($recentActivities) }}</span>
                    </div>
                    <div class="px-6 py-4 p-0">
                        @forelse($recentActivities as $activity)
                            <div class="flex items-center p-4 border-b border-gray-100 dark:border-slate-800 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600 dark:text-blue-400"></i>
                                    </div>
                                </div>
                                <div class="flex-grow">
                                    <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                        {{ $activity['kisi']['ad'] }} {{ $activity['kisi']['soyad'] }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ Str::limit($activity['aciklama'], 50) }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($activity['aktivite_tarihi'])->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400">Henüz aktivite bulunmuyor</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Stats - Takes 1/3 -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Hızlı İşlemler</h3>
                    </div>
                    <div class="px-6 py-4 p-4 space-y-3">
                        <a href="{{ route('admin.crm.customers.create') }}" class="flex items-center p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-user-plus text-blue-600 mr-3"></i>
                            <span class="text-gray-900 dark:text-white dark:text-slate-100">Yeni Müşteri Ekle</span>
                        </a>
                        <a href="{{ route('admin.crm.customers.index') }}" class="flex items-center p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-search text-green-600 mr-3"></i>
                            <span class="text-gray-900 dark:text-white dark:text-slate-100">Müşteri Ara</span>
                        </a>
                        <button onclick="generateReport()" class="w-full flex items-center p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-chart-bar text-purple-600 mr-3"></i>
                            <span class="text-gray-900 dark:text-white dark:text-slate-100">Rapor Oluştur</span>
                        </button>
                    </div>
                </div>

                <!-- Customer Types -->
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Müşteri Tipleri</h3>
                    </div>
                    <div class="px-6 py-4 p-4">
                        @foreach($customerSegments as $type => $count)
                            <div class="flex items-center justify-between mb-3 last:mb-0">
                                <span class="text-sm text-gray-600 dark:text-gray-400 capitalize">
                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                </span>
                                <span class="px-3 py-1 bg-gray-100 dark:bg-slate-900 text-gray-800 dark:text-slate-200 text-xs font-medium rounded-full">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function generateReport() {
                // Report generation logic here
                alert('Rapor oluşturma özelliği yakında eklenecek!');
            }
        </script>
    @endpush
@endsection
