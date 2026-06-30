// @context7-ignore-file
// Property Type Manager - Frontend Logic
// This file contains HTTP response handling (response code is NOT a database field)

function showAddKategoriModal() {
    const modal = document.getElementById('addKategoriModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => document.getElementById('modalKategoriName')?.focus(), 100);
    }
}

function closeAddKategoriModal() {
    const modal = document.getElementById('addKategoriModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.getElementById('addKategoriForm')?.reset();
}

async function addKategori(e) {
    e.preventDefault();

    const name = document.getElementById('modalKategoriName')?.value?.trim();
    const slug = document.getElementById('modalKategoriSlug')?.value?.trim();
    const icon = document.getElementById('modalKategoriIcon')?.value?.trim();

    if (!name || !slug) {
        alert('Kategori adı ve slug gerekli!');
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const response = await fetch('/admin/ilan-kategorileri/api/store', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                name,
                slug,
                icon: icon || '🏠'
            })
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                alert('✅ Kategori başarıyla eklendi! Sayfa yenileniyor...');
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('❌ ' + (data.message || 'Ekleme başarısız!'));
            }
        } else if (response.status === 422) {
            const error = await response.json();
            alert('❌ ' + (error.message || 'Doğrulama hatası!'));
        } else {
            alert('❌ Ekleme başarısız! HTTP ' + response.status);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Hata: ' + error.message);
    }
}

// Modal: ESC ve dış tıkla kapat
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addKategoriModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeAddKategoriModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeAddKategoriModal();
            }
        });
    }
});
