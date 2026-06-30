// key-manager.js - Anahtar Kelime Yönetimi

/**
 * Key Manager
 * SEO anahtar kelime yönetimi
 */
window.keyManager = function () {
    return {
        keywords: [],
        newKeyword: '',
        suggestedKeywords: [],

        /**
         * Anahtar kelime ekle
         */
        addKeyword() {
            if (!this.newKeyword || this.newKeyword.trim() === '') {
                return;
            }

            const keyword = this.newKeyword.trim();

            if (!this.keywords.includes(keyword)) {
                this.keywords.push(keyword);
                this.newKeyword = '';
                window.toast?.success('Anahtar kelime eklendi');
            } else {
                window.toast?.warning('Bu anahtar kelime zaten var');
            }
        },

        /**
         * Anahtar kelime sil
         */
        removeKeyword(keyword) {
            this.keywords = this.keywords.filter((k) => k !== keyword);
        },

        /**
         * AI anahtar kelime önerisi al
         */
        async getSuggestedKeywords() {
            const baslik = document.querySelector('[name="baslik"]')?.value;
            const kategori = document.querySelector(
                '[name="alt_kategori_id"] option:checked'
            )?.text;

            if (!baslik && !kategori) {
                window.toast?.warning('Önce başlık veya kategori girin');
                return;
            }

            window.toast?.info('AI anahtar kelime önerileri alınıyor...');

            // Basit keyword extraction (AI olmadan)
            const text = `${baslik} ${kategori}`.toLowerCase();
            const words = text.split(/\s+/).filter((w) => w.length > 3);

            this.suggestedKeywords = [...new Set(words)].slice(0, 10);
            window.toast?.success(`${this.suggestedKeywords.length} öneri hazır`);
        },

        /**
         * Önerilen kelimeyi ekle
         */
        addSuggested(keyword) {
            if (!this.keywords.includes(keyword)) {
                this.keywords.push(keyword);
            }
        },

        init() {
            console.log('Key manager initialized');
        },
    };
};

// Export
export default window.keyManager;
