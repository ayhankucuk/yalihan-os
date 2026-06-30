@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-slate-50 py-12 dark:bg-slate-900">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            <div class="mb-10 text-center">
                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Yalıhan AI Gayrimenkul Asistanı
                </h1>
                <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">
                    Piyasa fiyatlarını, bölge eğilimlerini ve yatırım fırsatlarını saniyeler içinde öğrenin.
                </p>
            </div>

            <!-- Chat Interface -->
            <div
                class="flex h-[70vh] max-h-[800px] flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800 dark:shadow-slate-950/20">

                <div id="ai-messages" class="flex-1 space-y-6 overflow-y-auto p-6">
                    <!-- Welcome Message bubble -->
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 max-w-2xl">
                            <div
                                class="rounded-2xl rounded-tl-none border border-slate-200 bg-slate-100 p-5 text-slate-800 shadow-sm dark:border-slate-600/50 dark:bg-slate-700/50 dark:text-slate-200">
                                <p class="mb-2 font-medium">Yalıhan Yapay Zeka Motoruna Hoş Geldiniz! 🚀</p>
                                <p class="mb-3 text-slate-600 dark:text-slate-400">Tüm piyasa verilerini analiz ederek size
                                    şu konularda yardımcı olabilirim:</p>
                                <ul class="space-y-2 text-sm">
                                    <li class="flex items-center text-indigo-700 dark:text-indigo-400">
                                        <svg class="mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        Bölgesel Fiyat Değerlemesi (Örn: "Bodrum Yalıkavak 500m2 arsa fiyatları?")
                                    </li>
                                    <li class="flex items-center text-indigo-700 dark:text-indigo-400">
                                        <svg class="mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        Piyasa Trend Analizi (Örn: "Urla bölgesinde trend nasıl?")
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="border-t border-slate-200 bg-slate-50 p-6 dark:border-slate-700 dark:bg-slate-800/80">
                    <form id="public-ai-form" class="relative">
                        @csrf
                        <div
                            class="flex items-center overflow-hidden rounded-full border border-slate-300 bg-white shadow-sm transition-all focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900">
                            <input type="text" id="public-query" name="query"
                                class="w-full flex-1 border-0 bg-transparent py-4 pl-6 pr-12 text-slate-900 placeholder-slate-500 focus:ring-0 dark:text-white"
                                placeholder="Aklınızdaki gayrimenkul sorusunu sorun..." autocomplete="off" required>
                            <button type="submit" id="public-submit-btn"
                                class="absolute right-2 flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-white transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50">
                                <svg class="h-5 w-5 rotate-90 transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                </svg>
                            </button>
                        </div>
                    </form>
                    <p class="mt-3 flex items-center justify-center text-center text-xs text-slate-500">
                        <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        SAB Standard Algoritmaları ile Güvence Altındadır.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('public-ai-form');
                const input = document.getElementById('public-query');
                const container = document.getElementById('ai-messages');
                const btn = document.getElementById('public-submit-btn');

                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const query = input.value.trim();
                    if (!query) return;

                    // Render User Bubble
                    renderUserMessage(query);
                    input.value = '';
                    btn.disabled = true;

                    const loadingId = renderLoadingIndicator();

                    try {
                        const response = await fetch('{{ route('public.conversational.query') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')
                                    .value,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                query: query
                            })
                        });

                        const data = await response.json();
                        document.getElementById(loadingId).remove();

                        if (data.is_success) {
                            renderAIMessage(data.advisor_response);
                        } else {
                            renderAIMessage(data.error || 'Geçici teknik arıza yaşanıyor.', true);
                        }

                    } catch (err) {
                        document.getElementById(loadingId).remove();
                        renderAIMessage('Sistem bağlantısı kurulamadı. Lütfen tekrar deneyin.', true);
                    } finally {
                        btn.disabled = false;
                        input.focus();
                    }
                });

                function renderUserMessage(text) {
                    const html = `
                <div class="flex items-start justify-end">
                    <div class="mr-4 max-w-2xl">
                        <div class="bg-indigo-600 rounded-2xl rounded-tr-none p-4 text-white shadow-sm">
                            <p>${escapeHtml(text)}</p>
                        </div>
                    </div>
                </div>
            `;
                    container.insertAdjacentHTML('beforeend', html);
                    scrollToBottom();
                }

                function renderAIMessage(text, isError = false) {
                    const bgClass = isError ? 'bg-red-50 dark:bg-red-900/30 border-red-200 text-red-700' :
                        'bg-white dark:bg-slate-700 border-slate-200 dark:border-slate-600 text-slate-800 dark:text-slate-200';
                    const iconBg = isError ? 'bg-red-500' : 'bg-gradient-to-br from-indigo-500 to-purple-600';

                    const html = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full ${iconBg} flex items-center justify-center shadow-md">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                ${isError ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />' : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />'}
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 max-w-2xl">
                        <div class="${bgClass} rounded-2xl rounded-tl-none p-5 shadow-sm border">
                            <p class="whitespace-pre-wrap">${escapeHtml(text)}</p>
                        </div>
                    </div>
                </div>
            `;
                    container.insertAdjacentHTML('beforeend', html);
                    scrollToBottom();
                }

                function renderLoadingIndicator() {
                    const id = 'loading-' + Date.now();
                    const html = `
                <div id="${id}" class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center animate-pulse"></div>
                    </div>
                    <div class="ml-4">
                        <div class="bg-white dark:bg-slate-700 rounded-2xl rounded-tl-none p-4 shadow-sm border border-slate-200 dark:border-slate-600 flex items-center space-x-2">
                            <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                        </div>
                    </div>
                </div>
            `;
                    container.insertAdjacentHTML('beforeend', html);
                    scrollToBottom();
                    return id;
                }

                function escapeHtml(unsafe) {
                    return unsafe
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;")
                        .replace(/'/g, "&#039;");
                }

                function scrollToBottom() {
                    container.scrollTop = container.scrollHeight;
                }
            });
        </script>
    @endpush
@endsection
