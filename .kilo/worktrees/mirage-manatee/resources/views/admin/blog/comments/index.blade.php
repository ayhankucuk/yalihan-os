@extends('admin.layouts.admin')

@section('title', 'Blog Yorumları Yönetimi')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    Blog Yorumları Yönetimi
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400 text-lg">Tüm blog yorumlarını görüntüleyin ve yönetin</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stat-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                </path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Yorum</p>
                            @php
                                $totalComments = ($stats['approved'] ?? 0) + ($stats['pending'] ?? 0) + ($stats['rejected'] ?? 0) + ($stats['spam'] ?? 0);
                            @endphp
                            <p class="stat-card-value">{{ $totalComments }}</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Onaylanan</p>
                            <p class="stat-card-value">{{ $stats['approved'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bekleyen</p>
                            <p class="stat-card-value">{{ $stats['pending'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728">
                                </path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Reddedilen</p>
                            <p class="stat-card-value">{{ $stats['rejected'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Actions -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6 mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="form-field">
                            <label for="status_filter" class="admin-label">Durum Filtresi</label>
                            <select style="color-scheme: light dark;" id="status_filter" class="admin-input transition-all duration-200">
                                <option value="">Tümü</option>
                                <option value="approved">Onaylanan</option>
                                <option value="pending">Bekleyen</option>
                                <option value="rejected">Reddedilen</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="post_filter" class="admin-label">Blog Yazısı</label>
                            <select style="color-scheme: light dark;" id="post_filter" class="admin-input transition-all duration-200">
                                <option value="">Tüm Yazılar</option>
                                @foreach ($posts as $post)
                                    <option value="{{ $post->id }}">{{ $post->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="button"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md dark:shadow-none"
                                onclick="applyFilters()">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                                </path>
                            </svg>
                            Filtrele
                        </button>
                        <button type="button"
                                class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none"
                                onclick="refreshComments()">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            Yenile
                        </button>
                    </div>
                </div>
            </div>

            <!-- Comments Table -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Yorum Listesi</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="admin-table-th">
                                    Yazar
                                </th>
                                <th class="admin-table-th">
                                    Yorum
                                </th>
                                <th class="admin-table-th">
                                    Blog Yazısı
                                </th>
                                <th class="admin-table-th">
                                    Durum
                                </th>
                                <th class="admin-table-th">
                                    Tarih
                                </th>
                                <th class="admin-table-th">
                                    İşlemler
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($comments as $comment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div
                                                    class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                                    {{ strtoupper(substr($comment->author_name, 0, 1)) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ $comment->author_name }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $comment->author_email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-white max-w-xs truncate dark:text-slate-100">
                                            {{ Str::limit($comment->content, 100) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ Str::limit($comment->post->title, 50) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($comment->yayin_durumu === 'approved')
                                            <span
                                                class="status-badge active">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                                Onaylandı
                                            </span>
                                        @elseif(($comment->yayin_durumu ?? 'pending') === 'pending')
                                            <span
                                                class="status-badge pending">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                                Bekliyor
                                            </span>
                                        @else
                                            <span
                                                class="status-badge inactive">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                                Reddedildi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $comment->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            @if ($comment->yayin_durumu === 'pending')
                                                <button onclick="approveComment({{ $comment->id }})"
                                                    class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all duration-200 shadow-sm hover:shadow-md dark:shadow-none">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    Onayla
                                                </button>
                                            @endif

                                            @if ($comment->yayin_durumu !== 'rejected')
                                                <button onclick="rejectComment({{ $comment->id }})"
                                                    class="inline-flex items-center px-3 py-1 text-xs font-medium bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md dark:shadow-none">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    Reddet
                                                </button>
                                            @endif

                                            <button onclick="markAsSpam({{ $comment->id }})"
                                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 text-xs px-3 py-1 touch-target-optimized dark:text-slate-300">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                                    </path>
                                                </svg>
                                                Spam
                                            </button>

                                            <button onclick="deleteComment({{ $comment->id }})"
                                                class="inline-flex items-center px-3 py-1 text-xs font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md dark:shadow-none">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                                Sil
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                                </path>
                                            </svg>
                                            <p class="text-lg font-medium dark:text-white">Henüz yorum bulunmuyor</p>
                                            <p class="text-sm dark:text-gray-400">Blog yazılarınız için yorumlar geldiğinde burada görünecek
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($comments->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800">
                        <div class="pagination">
                            {{ $comments->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Reject Comment Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-slate-900 dark:bg-opacity-75 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border border-gray-200 dark:border-slate-800 w-96 shadow-lg rounded-2xl bg-white dark:bg-slate-900 dark:border-slate-700">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Yorumu Reddet</h3>
                <div class="form-field">
                    <label for="reject_reason" class="admin-label">Red Nedeni</label>
                    <textarea id="reject_reason" class="admin-input" rows="3"
                        placeholder="Yorumu neden reddettiğinizi belirtin..."></textarea>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md dark:shadow-none"
                            onclick="closeRejectModal()">
                        İptal
                    </button>
                    <button type="button"
                            class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none"
                            onclick="confirmReject()">
                        Reddet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Spam Modal -->
    <div id="spamModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-slate-900 dark:bg-opacity-75 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border border-gray-200 dark:border-slate-800 w-96 shadow-lg rounded-2xl bg-white dark:bg-slate-900 dark:border-slate-700">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Spam Olarak İşaretle</h3>
                <div class="form-field">
                    <label for="spam_reason" class="admin-label">Spam Nedeni</label>
                    <textarea id="spam_reason" class="admin-input" rows="3"
                        placeholder="Bu yorumun neden spam olduğunu belirtin..."></textarea>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md dark:shadow-none"
                            onclick="closeSpamModal()">
                        İptal
                    </button>
                    <button type="button"
                            class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none"
                            onclick="confirmSpam()">
                        Spam Olarak İşaretle
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- ✅ DUPLICATE REMOVED: Common styles moved to resources/css/admin/common-styles.css --}}
    {{-- Bu sayfada sadece sayfa-spesifik stiller varsa buraya eklenebilir --}}
@endpush

@push('scripts')
    <script>
        let currentCommentId = null;

        function approveComment(commentId) {
            if (confirm('Bu yorumu onaylamak istediğinizden emin misiniz?')) {
                fetch(`/admin/blog/comments/${commentId}/approve`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Hata: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Bir hata oluştu');
                    });
            }
        }

        function rejectComment(commentId) {
            currentCommentId = commentId;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('reject_reason').value = '';
        }

        function confirmReject() {
            const reason = document.getElementById('reject_reason').value;

            fetch(`/admin/blog/comments/${currentCommentId}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        reason: reason
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeRejectModal();
                        location.reload();
                    } else {
                        alert('Hata: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu');
                });
        }

        function markAsSpam(commentId) {
            currentCommentId = commentId;
            document.getElementById('spamModal').classList.remove('hidden');
        }

        function closeSpamModal() {
            document.getElementById('spamModal').classList.add('hidden');
            document.getElementById('spam_reason').value = '';
        }

        function confirmSpam() {
            const reason = document.getElementById('spam_reason').value;

            fetch(`/admin/blog/comments/${currentCommentId}/spam`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        reason: reason
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeSpamModal();
                        location.reload();
                    } else {
                        alert('Hata: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu');
                });
        }

        function deleteComment(commentId) {
            if (confirm('Bu yorumu silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                fetch(`/admin/blog/comments/${commentId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Hata: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Bir hata oluştu');
                    });
            }
        }

        function applyFilters() {
            const yayin_durumu = document.getElementById('status_filter').value;
            const post = document.getElementById('post_filter').value;

            let url = new URL(window.location);
            if (yayin_durumu) url.searchParams.set('yayin_durumu', yayin_durumu);
            if (post) url.searchParams.set('post', post);

            window.location = url;
        }

        function refreshComments() {
            location.reload();
        }

        // Modal close on outside click
        document.addEventListener('click', function(event) {
            const rejectModal = document.getElementById('rejectModal');
            const spamModal = document.getElementById('spamModal');

            if (event.target === rejectModal) {
                closeRejectModal();
            }

            if (event.target === spamModal) {
                closeSpamModal();
            }
        });
    </script>
@endpush
