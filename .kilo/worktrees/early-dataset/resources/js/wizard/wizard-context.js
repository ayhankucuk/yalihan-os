export function getWizardContext() {
  const kategoriEl = document.querySelector('[data-kategori-slug]');
  const yayinTipiEl = document.querySelector('[data-yayin-tipi-slug]');
  const kategori = kategoriEl?.dataset?.kategoriSlug || null;
  const yayinTipi = yayinTipiEl?.dataset?.yayinTipiSlug || null;
  return {
    kategori_slug: kategori,
    yayin_tipi_slug: yayinTipi,
    isReady() {
      return Boolean(kategori && yayinTipi);
    },
  };
}

