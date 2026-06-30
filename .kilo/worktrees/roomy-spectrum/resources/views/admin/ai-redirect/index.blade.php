@extends('admin.layouts.admin')

@section('title', 'AI Redirect Management - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-route text-white text-xl"></i>
                    </div>
                    AI Redirect Management
                </h1>
                <p class="text-lg text-gray-600 mt-2">Manage AI system redirects and routing</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.ai-redirect.create') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none">
                    <i class="fas fa-plus mr-2"></i>
                    New Redirect
                </a>
                <a href="{{ route('admin.ai-redirect.analytics') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Analytics
                </a>
            </div>
        </div>
    </div>

    <div class="px-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
            <!-- Quick Redirect Actions -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <a href="{{ route('admin.ai-settings.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 text-center dark:shadow-none">
                    <i class="fas fa-cog mr-2"></i>
                    AI Settings
                </a>
                <a href="{{ route('admin.ai.advanced-dashboard') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm text-center dark:shadow-none dark:text-slate-300">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    AI Dashboard
                </a>
                <a href="{{ route('admin.danisman-ai.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-yellow-600 to-orange-600 rounded-lg hover:from-yellow-700 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 text-center dark:shadow-none">
                    <i class="fas fa-robot mr-2"></i>
                    Danışman AI
                </a>
                <a href="{{ route('admin.page-analyzer.dashboard') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-cyan-600 to-blue-600 rounded-lg hover:from-cyan-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 text-center dark:shadow-none">
                    <i class="fas fa-search mr-2"></i>
                    Page Analyzer
                </a>
            </div>

            <!-- Redirect Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-route text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Redirects</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">1,247</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Success Rate</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">99.8%</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-2xl text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Avg Response</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">45ms</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-star text-2xl text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Most Used</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">AI Settings</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Redirect Configuration -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Redirect Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Target Route
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Usage Count
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-slate-900">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                AI Settings Redirect
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                admin.ai-settings.index
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                856
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.ai-redirect.edit', 1) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                <a href="{{ route('admin.ai-redirect.show', 1) }}" class="text-blue-600 hover:text-blue-900">View</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                AI Dashboard Redirect
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                admin.ai.advanced-dashboard
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                234
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.ai-redirect.edit', 2) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                <a href="{{ route('admin.ai-redirect.show', 2) }}" class="text-blue-600 hover:text-blue-900">View</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                Danışman AI Redirect
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                admin.danisman-ai.index
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                123
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.ai-redirect.edit', 3) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                <a href="{{ route('admin.ai-redirect.show', 3) }}" class="text-blue-600 hover:text-blue-900">View</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                Page Analyzer Redirect
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                admin.page-analyzer.dashboard
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                34
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.ai-redirect.edit', 4) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                <a href="{{ route('admin.ai-redirect.show', 4) }}" class="text-blue-600 hover:text-blue-900">View</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
