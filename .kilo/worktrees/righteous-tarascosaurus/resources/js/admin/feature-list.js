// Özellik listesi (features) sayfası için JS (Alpine bağımsız minimal)

(function () {
    // Bu dosya Blade tarafından işlenmez; URL'ler global değişkenlerden veya varsayılanlardan alınır.
    const FEATURE_BASE_URL = window.FEATURES_BASE_URL || '/admin/ozellikler';
    const FEATURE_BULK_URL = window.FEATURES_BULK_URL || '/admin/ozellikler/bulk-action';
    const FEATURE_ORDER_URL = window.FEATURES_ORDER_URL || '/admin/ozellikler/reorder';

    function csrf() {
        const el = document.querySelector('meta[name=csrf-token]');
        return el ? el.content : '';
    }

    function reorderIds(tbody) {
        return [...tbody.querySelectorAll('tr[data-id]')].map((r) =>
            parseInt(r.getAttribute('data-id'))
        );
    }

    function init() {
        if (!window.Sortable) return;
        const tbody = document.querySelector('#features-table tbody');
        if (!tbody) return;

        // Drag & drop
        Sortable.create(tbody, {
            handle: '.cursor-move',
            animation: 150,
            onEnd() {
                const banner = document.getElementById('order-dirty-banner');
                if (banner) banner.classList.remove('hidden');
            },
        });

        // Kayıt butonu (opsiyonel)
        const saveBtn = document.getElementById('save-order-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const ids = reorderIds(tbody);
                fetch(FEATURE_ORDER_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                    },
                    body: JSON.stringify({ ids }),
                })
                    .then((r) => (r.ok ? r.json() : Promise.reject()))
                    .then(() => {
                        window.showToast && showToast('Sıralama kaydedildi', 'success');
                        const b = document.getElementById('order-dirty-banner');
                        if (b) b.classList.add('hidden');
                    })
                    .catch(
                        () => window.showToast && showToast('Sıralama kaydedilirken hata', 'error')
                    );
            });
        }

        // Durum toggle (event delegation)
        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-toggle]');
            if (!btn) return;
            const id = btn.getAttribute('data-id');
            fetch(`${FEATURE_BASE_URL}/${id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf() },
            })
                .then((r) => (r.ok ? r.json() : Promise.reject()))
                .then((d) => {
                    if (d.status === 'ok') {
                        if (d.status) {
                            btn.className =
                                'px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300';
                            btn.textContent = 'Aktif';
                        } else {
                            btn.className =
                                'px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
                            btn.textContent = 'Pasif';
                        }
                        window.showToast && showToast('Durum güncellendi', 'success');
                    }
                })
                .catch(() => window.showToast && showToast('Durum güncelleme hatası', 'error'));
        });

        // Toplu işlemler
        const master = document.getElementById('master-checkbox');
        const bulkActivate = document.getElementById('bulk-activate');
        const bulkDeactivate = document.getElementById('bulk-deactivate');
        const bulkDelete = document.getElementById('bulk-delete');

        function selectedBoxes() {
            return [...tbody.querySelectorAll('input[type=checkbox][data-row]')];
        }
        function selectedIds() {
            return selectedBoxes()
                .filter((c) => c.checked)
                .map((c) => parseInt(c.value));
        }

        master &&
            master.addEventListener('change', () => {
                const checked = master.checked;
                selectedBoxes().forEach((c) => (c.checked = checked));
            });

        function bulk(action) {
            const ids = selectedIds();
            if (!ids.length) return;
            fetch(FEATURE_BULK_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ ids, action }),
            })
                .then((r) => (r.ok ? r.json() : Promise.reject()))
                .then(() => {
                    window.showToast && showToast('Toplu işlem tamamlandı', 'success');
                    window.location.reload();
                })
                .catch(() => window.showToast && showToast('Toplu işlem hatası', 'error'));
        }

        bulkActivate && bulkActivate.addEventListener('click', () => bulk('activate'));
        bulkDeactivate && bulkDeactivate.addEventListener('click', () => bulk('deactivate'));
        bulkDelete &&
            bulkDelete.addEventListener('click', () => {
                if (confirm('Seçilenleri silmek istediğinizden emin misiniz?')) bulk('delete');
            });
    }

    document.addEventListener('DOMContentLoaded', init);
})();

// Not: Bu modül bağımsız çalışır; Blade içerisinde URL tanımlamak için sayfaya eklenmeden önce:
// <script>
//   window.FEATURES_BASE_URL = "{{ url('admin/ozellikler') }}";
//   window.FEATURES_BULK_URL = "{{ route('admin.ozellikler.features.bulk') }}";
//   window.FEATURES_ORDER_URL = "{{ route('admin.ozellikler.features.reorder') }}";
// </script>
// ekleyebilirsiniz.
