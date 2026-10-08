{{-- QR Code Settings Section --}}
<div class="space-y-6">
    <x-admin.form-alert type="info" title="QR Kod Özellikleri">
        İlanlar için QR kod oluşturma ve yönetim ayarları. QR kodlar mobil cihazlarla hızlı erişim sağlar.
    </x-admin.form-alert>

    <div class="space-y-6">
        <x-admin.form-field label="QR Kod Özelliğini Aktif Et" name="qrcode_durumu"
            hint="QR kod özelliğini tüm sistemde aktif/pasif yapar">
            <x-admin.toggle
                name="qrcode_durumu"
                :checked="($settings['qrcode_durumu'] ?? 'true') == 'true' || ($settings['qrcode_durumu'] ?? true) === true"
            />
        </x-admin.form-field>

        <x-admin.form-field label="Varsayılan QR Kod Boyutu (Piksel)" name="qrcode_default_size"
            hint="Önerilen: 200 (küçük), 300 (orta), 400 (büyük)">
            <x-admin.input
                type="number"
                name="qrcode_default_size"
                :value="old('qrcode_default_size', $settings['qrcode_default_size'] ?? '300')"
            />
        </x-admin.form-field>

        <x-admin.form-field label="İlan Kartlarında QR Kod Göster" name="qrcode_show_on_cards"
            hint="İlan listesi kartlarında QR kod butonu gösterilir">
            <x-admin.toggle
                name="qrcode_show_on_cards"
                :checked="($settings['qrcode_show_on_cards'] ?? 'true') == 'true' || ($settings['qrcode_show_on_cards'] ?? true) === true"
            />
        </x-admin.form-field>

        <x-admin.form-field label="İlan Detay Sayfasında QR Kod Göster" name="qrcode_show_on_detail"
            hint="İlan detay sayfalarında QR kod widget'ı gösterilir">
            <x-admin.toggle
                name="qrcode_show_on_detail"
                :checked="($settings['qrcode_show_on_detail'] ?? 'true') == 'true' || ($settings['qrcode_show_on_detail'] ?? true) === true"
            />
        </x-admin.form-field>
    </div>
</div>
