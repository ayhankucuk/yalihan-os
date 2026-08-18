<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Apex landing page for yalihanemlak.com.tr cutover.
 *
 * Minimal coming-soon page shown during platform migration.
 * Does NOT perform any DB queries — fully production-safe.
 *
 * Legal pages (/legal/*) are real content served from this same app.
 */
class ApexLandingController extends Controller
{
    /**
     * Apex coming-soon landing page.
     *
     * This page is served when yalihanemlak.com.tr DNS points to
     * the YALIHAN OS Hetzner server. It replaces the legacy hosting
     * with a clean migration placeholder while the full website is built.
     */
    public function index(): View|Response
    {
        return view('frontend.legal.apex-landing', [
            'seo' => [
                'title' => 'Yalıhan Emlak — Web Sitemiz Yenileniyor',
                'description' => 'Yalıhan Emlak\'ın yeni web sitesi hazırlanıyor. Bodrum\'da lüks gayrimenkul danışmanlığı.',
                'og_type' => 'website',
            ],
        ]);
    }
}
