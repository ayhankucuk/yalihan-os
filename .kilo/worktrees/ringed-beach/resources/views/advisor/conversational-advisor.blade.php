@extends('admin.layouts.admin')

@section('title', 'AI Conversational Advisor')

@section('content')
    <div class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                AI Danışman
            </h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Piyasa analizi, değerleme ve portföy eşleştirme sorularınızı doğal dilde sorabilirsiniz.
            </p>
        </div>

        <!-- Chat Container -->
        <div
            class="flex h-[600px] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
            <!-- Messages Area -->
            <div id="chat-messages" class="flex-1 space-y-4 overflow-y-auto p-6">

                <!-- System Welcome Message -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                    </div>
                    <div class="max-w-[80%] rounded-2xl rounded-tl-none bg-slate-50 px-4 py-3 dark:bg-slate-800">
                        <p class="text-slate-700 dark:text-slate-300">Merhaba! Size nasıl yardımcı olabilirim? Örnek
                            sorular:</p>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-slate-500 dark:text-slate-400">
                            <li>Bodrum Bitez bölgesinde 1 dönüm tarla ortalama fiyatı nedir?</li>
                            <li>Kuşadası bölgesinde satış trendi nasıl?</li>
                        </ul>
                    </div>
                </div>

            </div>

            <!-- Input Area -->
            <div class="border-t border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <form id="ai-chat-form" class="relative flex items-center">
                    @csrf
                    <input type="text" id="chat-input"
                        class="w-full rounded-full border border-slate-300 bg-slate-50 py-3 pl-4 pr-12 text-slate-900 transition-colors focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        placeholder="Yapay zekaya danışın..." autocomplete="off" required>
                    <button type="submit" id="send-button"
                        class="absolute right-2 rounded-full bg-indigo-600 p-2 text-white transition-colors hover:bg-indigo-700 disabled:opacity-50">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('ai-chat-form');
                const input = document.getElementById('chat-input');
                const messagesContainer = document.getElementById('chat-messages');
                const submitBtn = document.getElementById('send-button');

                form.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const query = input.value.trim();
                    if (!query) return;

                    // 1. Add User Message to UI
                    appendMessage('user', query);
                    input.value = '';

                    // 2. Show Loading State
                    submitBtn.disabled = true;
                    const loadingId = appendLoading();

                    try {
                        // 3. Send to API Endpoint
                        const response = await fetch('{{ route('advisor.conversational.query') }}', {
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

                        // 4. Remove Loading & Add AI Response
                        removeLoading(loadingId);

                        if (data.is_success) {
                            appendMessage('ai', data.advisor_response, data.data_payload);
                        } else {
                            appendMessage('ai', data.error || 'Bir hata oluştu.', null, true);
                        }

                    } catch (error) {
                        removeLoading(loadingId);
                        appendMessage('ai', 'Sunucuya bağlanılamadı. Lütfen tekrar deneyin.', null, true);
                    } finally {
                        submitBtn.disabled = false;
                        input.focus();
                    }
                });

                function appendMessage(role, text, payload = null, isError = false) {
                    const wrapper = document.createElement('div');
                    wrapper.className = `flex items-start gap-4 ${role === 'user' ? 'flex-row-reverse' : ''}`;

                    let avatar = '';
                    let bubbleClass = '';

                    if (role === 'user') {
                        avatar = `<div class="flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700">
                                <span class="font-medium text-slate-600 dark:text-slate-300">S</span>
                              </div>`;
                        bubbleClass = 'bg-indigo-600 text-white rounded-2xl rounded-tr-none';
                    } else {
                        avatar = `<div class="flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                </svg>
                              </div>`;
                        bubbleClass = isError ?
                            'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' :
                            'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300';
                        bubbleClass += ' rounded-2xl rounded-tl-none';
                    }

                    let payloadHtml = '';
                    if (payload && Object.keys(payload).length > 0) {
                        payloadHtml = `<div class="mt-3 p-3 bg-white/50 dark:bg-black/20 rounded-lg text-xs font-mono overflow-auto max-w-full">
                                    <pre>${JSON.stringify(payload, null, 2)}</pre>
                                   </div>`;
                    }

                    wrapper.innerHTML = `
                    ${avatar}
                    <div class="px-4 py-3 max-w-[80%] ${bubbleClass} shadow-sm">
                        <p class="whitespace-pre-wrap">${text}</p>
                        ${payloadHtml}
                    </div>
                `;

                    messagesContainer.appendChild(wrapper);
                    scrollToBottom();
                }

                function appendLoading() {
                    const id = 'loading-' + Date.now();
                    const wrapper = document.createElement('div');
                    wrapper.id = id;
                    wrapper.className = `flex items-start gap-4`;

                    wrapper.innerHTML = `
                    <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                        <svg class="w-6 h-6 animate-pulse text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                    </div>
                    <div class="px-4 py-3 max-w-[80%] bg-slate-50 dark:bg-slate-800 rounded-2xl rounded-tl-none flex items-center gap-1">
                        <div class="w-2 h-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                `;

                    messagesContainer.appendChild(wrapper);
                    scrollToBottom();
                    return id;
                }

                function removeLoading(id) {
                    const el = document.getElementById(id);
                    if (el) el.remove();
                }

                function scrollToBottom() {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            });
        </script>
    @endpush
@endsection
