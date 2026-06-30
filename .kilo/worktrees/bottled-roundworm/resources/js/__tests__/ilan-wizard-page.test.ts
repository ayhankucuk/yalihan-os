import { describe, it, expect, beforeEach, vi } from 'vitest'

// Yardımcı: DOM kurulumunu yap
function setupFormDOM() {
  document.body.innerHTML = `
    <form id="ilan-wizard-form" action="/admin/ilanlar/store" method="POST">
      <input name="baslik" required />
      <input name="fiyat" id="fiyat" value="5.000" required />
      <input type="hidden" name="fiyat_raw" id="fiyat_raw" value="5000" />
      <select name="para_birimi" id="para_birimi" required>
        <option value="TRY" selected>TRY</option>
      </select>
      <select name="ana_kategori_id" id="ana_kategori_id" required>
        <option value="">Seçin</option>
        <option value="1" data-slug="arsa" selected>Arsa</option>
      </select>
      <select name="alt_kategori_id" id="alt_kategori_id" required>
        <option value="">Seçin</option>
        <option value="11" data-slug="arsa-parsel" selected>Arsa Parsel</option>
      </select>
      <select name="yayin_tipi_id" id="yayin_tipi_id" required>
        <option value="21" data-slug="satilik" selected>Satılık</option>
      </select>
      <select name="il_id" id="il_id" required>
        <option value="">Seçin</option>
        <option value="48" selected>Muğla</option>
      </select>
      <select name="ilce_id" id="ilce_id" required>
        <option value="">Seçin</option>
        <option value="1" selected>Bodrum</option>
      </select>
      <textarea name="adres" id="adres" required>Adres</textarea>

      <!-- Step 2 Arsa alanları -->
      <input name="alan_m2" id="alan_m2" required />
      <select name="imar_statusu" id="imar_statusu" required>
        <option value="imarlı">imarlı</option>
      </select>

      <!-- Step 2 Konut alanları -->
      <select name="oda_sayisi" id="oda_sayisi" required>
        <option value="2+1">2+1</option>
      </select>
      <select name="banyo_sayisi" id="banyo_sayisi" required>
        <option value="1">1</option>
      </select>
      <input name="brut_alan" id="brut_alan" required />
      <input name="net_alan" id="net_alan" required />

      <button type="submit">✅ Yayınla</button>
    </form>
    `
}

describe('ilan-wizard-page.js', () => {
  beforeEach(() => {
    setupFormDOM()
    vi.stubGlobal('fetch', vi.fn(async () => ({
      ok: true,
      headers: new Map([['content-type', 'application/json']]),
      json: async () => ({ success: true, redirect: '/admin/ilanlar' })
    })))
    // Scripti yükle
    // Vitest jsdom içerisinde IIFE çalışacak ve window.ilanWizard atanacak
    // eslint-disable-next-line @typescript-eslint/no-var-requires
    require('../../js/admin/ilan-wizard-page.js')
  })

  it('Step 1 alanlarını döndürür', () => {
    const wizard = (window as any).ilanWizard()
    const fields = wizard.getStepFields(1)
    expect(fields).toEqual([
      'ana_kategori_id', 'alt_kategori_id', 'yayin_tipi_id',
      'baslik', 'fiyat', 'para_birimi', 'il_id', 'ilce_id', 'adres'
    ])
  })

  it('Step 2 arsa alanlarını algılar', () => {
    const alt = document.getElementById('alt_kategori_id') as HTMLSelectElement
    // Arsa senaryosu
    alt.selectedIndex = 1 // data-slug="arsa-parsel"
    const wizard = (window as any).ilanWizard()
    const fields = wizard.getStepFields(2)
    // Dinamik required taraması; arsa için alan_m2 ve imar_statusu beklenir
    expect(fields).toEqual(['alan_m2', 'imar_statusu'])
  })

  it('Step 2 konut alanlarını algılar', () => {
    const alt = document.getElementById('alt_kategori_id') as HTMLSelectElement
    // Konut senaryosu: data-slug olmadan metinden slug üreterek test edelim
    alt.options[1].setAttribute('data-slug', 'konut-daite')
    alt.options[1].text = 'Konut Daire'
    alt.selectedIndex = 1
    const wizard = (window as any).ilanWizard()
    const fields = wizard.getStepFields(2)
    expect(fields).toEqual(['oda_sayisi', 'banyo_sayisi', 'brut_alan', 'net_alan'])
  })

  it('category-changed event detaylarını doğru dispatch eder', () => {
    const wizard = (window as any).ilanWizard()
    let received: any = null
    window.addEventListener('category-changed', (e: any) => { received = e.detail })
    wizard.triggerCategoryChangedIfNeeded()
    expect(received).toBeTruthy()
    expect(received.category.slug).toBe('arsa')
    expect(received.altCategory.slug).toBe('arsa-parsel')
    expect(received.yayinTipi.slug).toBe('satilik')
  })

  it('submitForm fiyatı raw değere çevirir ve fetch çağırır', async () => {
    const wizard = (window as any).ilanWizard()
    // Step 3 doğrulamasını bypass etmek için gerekli alanları ekleyelim
    const aciklama = document.createElement('textarea')
    aciklama.name = 'aciklama'
    aciklama.setAttribute('required', 'required')
    aciklama.value = 'Bu açıklama en az 50 karakter içerir ve geçerlidir.'.repeat(2)
    document.getElementById('ilan-wizard-form')!.appendChild(aciklama)
    const ilanSahibi = document.createElement('input')
    ilanSahibi.name = 'ilan_sahibi_id'
    ilanSahibi.setAttribute('required', 'required')
    ilanSahibi.value = '1'
    document.getElementById('ilan-wizard-form')!.appendChild(ilanSahibi)
    const status = document.createElement('input')
    status.name = 'status'
    status.setAttribute('required', 'required')
    status.value = 'aktif'
    document.getElementById('ilan-wizard-form')!.appendChild(status)

    await wizard.submitForm()
    const fiyatInput = document.getElementById('fiyat') as HTMLInputElement
    expect(fiyatInput.value).toBe('5000')
    expect((globalThis as any).fetch).toHaveBeenCalled()
  })
})

