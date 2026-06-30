    <!-- Kategori-Specific Section Visibility -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mainCategorySelect = document.querySelector('select[name="ana_kategori_id"]');

            if (mainCategorySelect) {
                mainCategorySelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const categorySlug = selectedOption.getAttribute('data-slug') || '';

                    // Tüm kategori-specific section'ları al
                    const specificSections = document.querySelectorAll('.kategori-specific-section');

                    specificSections.forEach(section => {
                        const showFor = section.getAttribute('data-show-for-categories') || '';

                        // Konut kategorisi ise göster, değilse gizle
                        if (categorySlug.includes('konut') || categorySlug.includes('daire') ||
                            categorySlug.includes('villa')) {
                            if (showFor === 'konut') {
                                section.style.display = 'block';
                            } else {
                                section.style.display = 'none';
                            }
                        } else if (categorySlug.includes('yazlik')) {
                            if (showFor === 'yazlik') {
                                section.style.display = 'block';
                            } else {
                                section.style.display = 'none';
                            }
                        } else {
                            section.style.display = 'none';
                        }
                    });

                    console.log('✅ Kategori değişti:', categorySlug);
                });

                // Sayfa yüklendiğinde de kontrol et (edit mode için)
                if (window.editMode && window.ilanData?.anaKategori) {
                    const categorySlug = window.ilanData.anaKategori.slug || '';
                    const specificSections = document.querySelectorAll('.kategori-specific-section');

                    specificSections.forEach(section => {
                        const showFor = section.getAttribute('data-show-for-categories') || '';

                        if (categorySlug.includes('konut') && showFor === 'konut') {
                            section.style.display = 'block';
                        } else if (categorySlug.includes('yazlik') && showFor === 'yazlik') {
                            section.style.display = 'block';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                }

                mainCategorySelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
