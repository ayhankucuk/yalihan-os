<?php

namespace App\DataTransferObjects\Finans;

class UpdateFinansalIslemCommand
{
    public function __construct(
        public readonly ?string $islemTipi = null,
        public readonly float|int|string|null $miktar = null,
        public readonly ?string $paraBirimi = null,
        public readonly ?string $tarih = null,
        public ?string $islemStatusu = null,
        public readonly ?string $aciklama = null,
        public readonly ?string $referansNo = null,
        public readonly ?string $faturaNo = null,
        public readonly ?string $notlar = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            islemTipi: $data['islem_tipi'] ?? null,
            miktar: $data['miktar'] ?? null,
            paraBirimi: $data['para_birimi'] ?? null,
            tarih: $data['tarih'] ?? null,
            islemStatusu: $data['islem_statusu'] ?? null,
            aciklama: $data['aciklama'] ?? null,
            referansNo: $data['referans_no'] ?? null,
            faturaNo: $data['fatura_no'] ?? null,
            notlar: $data['notlar'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'islem_tipi' => $this->islemTipi,
            'miktar' => $this->miktar,
            'para_birimi' => $this->paraBirimi,
            'tarih' => $this->tarih,
            'islem_statusu' => $this->islemStatusu,
            'aciklama' => $this->aciklama,
            'referans_no' => $this->referansNo,
            'fatura_no' => $this->faturaNo,
            'notlar' => $this->notlar,
        ], fn($value) => $value !== null);
    }
}
