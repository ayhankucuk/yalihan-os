<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Legal pages controller for Meta App Publish compliance.
 *
 * Creates public HTTPS endpoints required for:
 * - Privacy Policy (Gizlilik Politikası)
 * - Terms of Service (Kullanım Şartları)
 * - Data Deletion Instructions (Veri Silme Talimatı)
 */
class LegalController extends Controller
{
    /**
     * Privacy Policy page.
     */
    public function privacy(): View|Response
    {
        return view('frontend.legal.privacy', [
            'seo' => [
                'title' => 'Gizlilik Politikası — Yalıhan Emlak',
                'description' => 'Yalıhan Emlak gizlilik politikası. Kişisel verilerinizin nasıl toplandığı, kullanıldığı ve korunduğu hakkında bilgi edinin.',
                'og_type' => 'website',
            ],
        ]);
    }

    /**
     * Terms of Service page.
     */
    public function terms(): View|Response
    {
        return view('frontend.legal.terms', [
            'seo' => [
                'title' => 'Kullanım Şartları — Yalıhan Emlak',
                'description' => 'Yalıhan Emlak web sitesi ve hizmetlerinin kullanım şartları ve koşulları.',
                'og_type' => 'website',
            ],
        ]);
    }

    /**
     * Data deletion instructions page.
     * Required by Meta App Center for user data deletion compliance.
     */
    public function dataDeletion(): View|Response
    {
        return view('frontend.legal.data-deletion', [
            'seo' => [
                'title' => 'Veri Silme Talimatı — Yalıhan Emlak',
                'description' => 'Yalıhan Emlak hesabınızdan verilerinizin nasıl silineceğine dair talimatlar.',
                'og_type' => 'website',
            ],
        ]);
    }
}
