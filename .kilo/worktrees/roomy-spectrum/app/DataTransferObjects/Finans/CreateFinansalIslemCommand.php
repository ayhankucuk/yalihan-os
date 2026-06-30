<?php

namespace App\DataTransferObjects\Finans;

class CreateFinansalIslemCommand
{
    public function __construct(
        public readonly ?int $ilanId,
        public readonly ?int $kisiId,
        public readonly ?int $gorevId,
        public readonly string $islemTipi,
        public readonly float|string $miktar,
        public readonly string $paraBirimi,
        public readonly string $tarih,
        public readonly ?string $aciklama = null,
        public readonly ?string $referansNo = null,
        public readonly ?string $faturaNo = null,
        public readonly ?string $notlar = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            ilanId: $data['ilan_id'] ?? null,
            kisiId: $data['kisi_id'] ?? null,
            gorevId: $data['gorev_id'] ?? null,
            islemTipi: $data['islem_tipi'],
            miktar: $data['miktar'],
            paraBirimi: $data['para_birimi'],
            tarih: $data['tarih'],
            aciklama: $data['aciklama'] ?? null,
            referansNo: $data['referans_no'] ?? null,
            faturaNo: $data['fatura_no'] ?? null,
            notlar: $data['notlar'] ?? null,
        );
    }
}
