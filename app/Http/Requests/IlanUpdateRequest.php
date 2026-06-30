<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * IlanUpdateRequest
 * 
 * Context7: C7-ILAN-UPDATE-REQUEST-2025-12-27
 */
class IlanUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'baslik' => 'sometimes|required|string|max:255',
            'aciklama' => 'nullable|string',
            'fiyat' => 'sometimes|required|numeric|min:0',
            'para_birimi' => 'sometimes|required|string|max:10',
            
            // ✅ SAB: yayin_durumu (publication status)
            'yayin_durumu' => 'sometimes|required|boolean',
            
            'ana_kategori_id' => 'sometimes|required|exists:ilan_kategorileri,id',
            'alt_kategori_id' => 'nullable|exists:ilan_kategorileri,id',
            'yayin_tipi_id' => 'nullable|exists:ilan_kategorileri,id',
            
            'il_id' => 'sometimes|required|exists:iller,id',
            'ilce_id' => 'nullable|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            
            'adres' => 'nullable|string',
            'lat' => 'nullable|numeric',   // ✅ SAB
            'lng' => 'nullable|numeric',   // ✅ SAB
            
            'oda_sayisi' => 'nullable|integer|min:0',
            'banyo_sayisi' => 'nullable|integer|min:0',
            'net_m2' => 'nullable|numeric|min:0',
            'brut_m2' => 'nullable|numeric|min:0',
            'kat' => 'nullable|integer',
            'toplam_kat' => 'nullable|integer',
            
            'ada_no' => 'nullable|string|max:50',
            'parsel_no' => 'nullable|string|max:50',
            'alan_m2' => 'nullable|numeric|min:0',
        ];
    }
}
