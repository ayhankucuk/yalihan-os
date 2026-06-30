<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

use App\Enums\IlanDurumu;
use App\Services\Ilan\IlanSearchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ✅ Sprint 1 - Öncelik 1: MyListingsExportController
 *
 * Export functionality extracted from MyListingsController
 * Handles Excel and PDF export operations for user's listings
 *
 * SAB Compliance:
 * - Thin controller pattern
 * - Context7 naming (yayin_durumu, not status)
 * - Repository pattern (IlanSearchService)
 * - Pure delegation
 */
class MyListingsExportController extends AdminController
{
    protected IlanSearchService $searchService;

    public function __construct(IlanSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Export listings to Excel/PDF
     *
     * Context7 Standardı: C7-MYLISTINGS-EXPORT-2025-11-05
     *
     * GET /admin/my-listings/export?format=excel|pdf
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'nullable|in:excel,pdf',
        ]);

        $user = Auth::user();
        $format = $request->input('format', 'excel');

        // ── 1. QUERY AUTHORITY: Get all listings for export ──
        $listings = $this->searchService->getAllMyListingsForExport($user->id, $request->all());

        if ($format === 'pdf') {
            return $this->exportPdf($listings, $user);
        }

        return $this->exportExcel($listings, $user);
    }

    /**
     * Export to Excel
     *
     * @param \Illuminate\Database\Eloquent\Collection $listings
     * @param \App\Models\User $user
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    protected function exportExcel($listings, $user)
    {
        $data = [
            ['İlanlarım - Excel Raporu'],
            ['Danışman', $user->name],
            ['Email', $user->email],
            ['Tarih', now()->format('d.m.Y H:i')],
            ['Toplam İlan', $listings->count()],
            [''],
            [
                'ID', 'Referans No', 'Başlık', 'Kategori', 'İl', 'İlçe',
                'Fiyat', 'Para Birimi', 'Durum', 'Görüntülenme', 'Oluşturulma Tarihi'
            ],
        ];

        foreach ($listings as $listing) {
            $data[] = [
                $listing->id,
                $listing->referans_no ?? '-',
                $listing->baslik ?? 'Başlıksız',
                $listing->altKategori?->name ?? $listing->anaKategori?->name ?? '-',
                $listing->il?->il_adi ?? '-',
                $listing->ilce?->ilce_adi ?? '-',
                $listing->fiyat ?? 0,
                $listing->para_birimi ?? 'TL',
                $listing->yayin_durumu ?? IlanDurumu::YAYINDA->value,
                $listing->goruntulenme ?? 0,
                $listing->created_at?->format('d.m.Y H:i') ?? '-',
            ];
        }

        $dosyaAdi = 'Ilanlarim_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray
        {
            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }
        }, $dosyaAdi);
    }

    /**
     * Export to PDF
     *
     * @param \Illuminate\Database\Eloquent\Collection $listings
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    protected function exportPdf($listings, $user)
    {
        $data = [
            'listings' => $listings,
            'user' => $user,
            'tarih' => now()->format('d.m.Y H:i'),
        ];

        $pdf = Pdf::loadView('admin.ilanlar.exports.my-listings-pdf', $data);

        $dosyaAdi = 'Ilanlarim_'.now()->format('Ymd_His').'.pdf';

        return $pdf->download($dosyaAdi);
    }
}
