/**
 * 🖱️ Yayın Tipleri Drag & Drop Sistemi
 * İlan Kategorileri sisteminden uyarlandı
 */

document.addEventListener('DOMContentLoaded', function () {
    initDragAndDrop();
    initToastSystem();
    initKeyboardSupport();
});

// Global değişkenler
let draggedElement = null;
let draggedData = null;
let originalPosition = null;

/**
 * Ana Drag & Drop sistemini başlat
 */
function initDragAndDrop() {
    const sortableContainer = document.getElementById('sortable-yayin-tipleri');

    if (!sortableContainer) {
        console.warn('Sortable container bulunamadı');
        return;
    }

    console.log('🎯 Yayın Tipleri Drag & Drop sistemi başlatılıyor...');

    // Yayın tipleri için drag & drop
    sortableContainer.addEventListener('dragstart', handleDragStart);
    sortableContainer.addEventListener('dragend', handleDragEnd);
    sortableContainer.addEventListener('dragover', handleDragOver);
    sortableContainer.addEventListener('drop', handleDrop);
    sortableContainer.addEventListener('dragenter', handleDragEnter);
    sortableContainer.addEventListener('dragleave', handleDragLeave);
}

/**
 * Drag başlangıcı
 */
function handleDragStart(e) {
    if (!e.target.classList.contains('yayin-tipi-item')) {
        return;
    }

    draggedElement = e.target;
    originalPosition = Array.from(e.target.parentNode.children).indexOf(e.target);

    draggedData = {
        id: e.target.dataset.yayinTipiId,
        type: 'yayin-tipi',
    };

    // Visual feedback
    e.target.style.opacity = '0.5';
    e.target.classList.add('dragging', 'sortable-chosen');

    // Drag & drop için veri ayarla
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', e.target.outerHTML);

    // Drop zone'ları göster
    showAllDropZones();

    console.log('🎯 Drag başlatıldı:', draggedData);
}

/**
 * Drag bitişi
 */
function handleDragEnd(e) {
    if (!e.target.classList.contains('yayin-tipi-item')) {
        return;
    }

    e.target.style.opacity = '1';
    e.target.classList.remove('dragging', 'sortable-chosen');

    hideAllDropZones();

    // Genel temizlik
    draggedElement = null;
    draggedData = null;
    originalPosition = null;

    console.log('✅ Drag tamamlandı');
}

/**
 * Drag over
 */
function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';

    if (draggedElement && draggedData) {
        const afterElement = getDragAfterElement(e.currentTarget, e.clientY);
        highlightDropZone(afterElement);
    }
}

/**
 * Drag enter
 */
function handleDragEnter(e) {
    e.preventDefault();

    if (e.target.classList.contains('drop-zone-indicator')) {
        e.target.classList.add('active');
    }
}

/**
 * Drag leave
 */
function handleDragLeave(e) {
    if (e.target.classList.contains('drop-zone-indicator')) {
        e.target.classList.remove('active');
    }
}

/**
 * Drop event
 */
function handleDrop(e) {
    e.preventDefault();

    if (!draggedElement || !draggedData) return;

    const container = document.getElementById('sortable-yayin-tipleri');
    if (!container) return;

    const afterElement = getDragAfterElement(container, e.clientY);

    // Element'i yeni pozisyona taşı
    if (afterElement == null) {
        container.appendChild(draggedElement);
    } else {
        container.insertBefore(draggedElement, afterElement);
    }

    // Veritabanında sıralamayı güncelle
    updateYayinTipiOrder(container);

    hideAllDropZones();

    console.log('📦 Drop tamamlandı, sıralama güncelleniyor...');
}

/**
 * En yakın element'i bul
 */
function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.yayin-tipi-item:not(.dragging)')];

    return draggableElements.reduce(
        (closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        },
        { offset: Number.NEGATIVE_INFINITY }
    ).element;
}

/**
 * Drop zone'ları göster
 */
function showAllDropZones() {
    const indicators = document.querySelectorAll('.drop-zone-indicator');
    indicators.forEach((indicator) => {
        indicator.classList.remove('hidden');
        indicator.style.display = 'block';
    });
}

/**
 * Drop zone'ları gizle
 */
function hideAllDropZones() {
    const indicators = document.querySelectorAll('.drop-zone-indicator');
    indicators.forEach((indicator) => {
        indicator.classList.add('hidden');
        indicator.classList.remove('active');
        indicator.style.display = 'none';
    });
}

/**
 * Drop zone highlight
 */
function highlightDropZone(element) {
    // Önceki highlight'ları temizle
    document.querySelectorAll('.drop-zone-indicator.active').forEach((el) => {
        el.classList.remove('active');
    });

    if (element) {
        const indicator = element.querySelector('.drop-zone-indicator');
        if (indicator) {
            indicator.classList.add('active');
        }
    }
}

/**
 * Yayın tipi sıralamasını güncelle
 */
async function updateYayinTipiOrder(container) {
    const yayinTipleri = [];

    container.querySelectorAll('.yayin-tipi-item').forEach((item, index) => {
        yayinTipleri.push({
            id: parseInt(item.dataset.yayinTipiId),
            order: index + 1,
        });
    });

    console.log('📤 Gönderilen sıralama verisi:', yayinTipleri);

    try {
        const response = await fetch('/admin/yayin-tipleri/reorder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content'),
                Accept: 'application/json',
            },
            body: JSON.stringify({ yayin_tipleri: yayinTipleri }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Sıralama güncellenemedi');
        }

        // Başarılı güncelleme
        updateOrderNumbers();
        showToast(data.message || 'Yayın tipi sırası güncellendi!', 'success');

        console.log('✅ Sıralama başarıyla güncellendi');
    } catch (error) {
        console.error('❌ Sıralama hatası:', error);
        showToast('Sıralama güncellenirken hata oluştu: ' + error.message, 'error');

        // Hata durumunda sayfayı yenile
        setTimeout(() => {
            location.reload();
        }, 2000);
    }
}

/**
 * Sıra numaralarını güncelle
 */
function updateOrderNumbers() {
    document.querySelectorAll('.yayin-tipi-item').forEach((item, index) => {
        const numberElement = item.querySelector('.order-number');
        if (numberElement) {
            numberElement.textContent = index + 1;
        }
    });
}

/**
 * Toast bildirim sistemi
 */
function initToastSystem() {
    // Toast container yoksa oluştur
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }
}

/**
 * Toast göster
 */
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toast-container');

    const toast = document.createElement('div');
    const toastId = 'toast-' + Date.now();
    toast.id = toastId;

    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const icon =
        type === 'success'
            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';

    toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 flex items-center`;

    toast.innerHTML = `
        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            ${icon}
        </svg>
        <span class="font-medium">${message}</span>
        <button onclick="hideToast('${toastId}')" class="ml-4 text-white hover:text-gray-200 focus:outline-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;

    container.appendChild(toast);

    // Show animation
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);

    // Auto hide
    setTimeout(() => {
        hideToast(toastId);
    }, duration);

    return toastId;
}

/**
 * Toast gizle
 */
function hideToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast) {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }
}

/**
 * Klavye desteği
 */
function initKeyboardSupport() {
    document.addEventListener('keydown', function (e) {
        const focusedItem = document.activeElement;

        if (focusedItem && focusedItem.classList.contains('yayin-tipi-item')) {
            switch (e.key) {
                case 'ArrowUp':
                    e.preventDefault();
                    moveYayinTipiUp(focusedItem);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    moveYayinTipiDown(focusedItem);
                    break;
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    toggleYayinTipiEdit(focusedItem);
                    break;
                case 'Escape':
                    e.preventDefault();
                    focusedItem.blur();
                    break;
            }
        }
    });
}

/**
 * Yayın tipini yukarı taşı (klavye ile)
 */
function moveYayinTipiUp(element) {
    const previousSibling = element.previousElementSibling;
    if (previousSibling) {
        element.parentNode.insertBefore(element, previousSibling);
        element.focus();
        updateYayinTipiOrder(element.parentNode);
    }
}

/**
 * Yayın tipini aşağı taşı (klavye ile)
 */
function moveYayinTipiDown(element) {
    const nextSibling = element.nextElementSibling;
    if (nextSibling) {
        element.parentNode.insertBefore(nextSibling, element);
        element.focus();
        updateYayinTipiOrder(element.parentNode);
    }
}

/**
 * Yayın tipi düzenleme aç/kapat (klavye ile)
 */
function toggleYayinTipiEdit(element) {
    const editButton = element.querySelector('[href*="edit"]');
    if (editButton) {
        editButton.click();
    }
}

// Global fonksiyonları window objesine ekle (debugging için)
window.YayinTipleriDragDrop = {
    showToast,
    hideToast,
    updateYayinTipiOrder,
    updateOrderNumbers,
};

console.log('🎯 Yayın Tipleri Drag & Drop sistemi yüklendi!');
