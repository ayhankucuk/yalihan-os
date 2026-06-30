<?php

namespace App\Observers;

use App\Models\UpsTemplate;
use App\Models\TemplateVersion;

/**
 * UpsTemplate Version Observer
 *
 * UpsTemplate (V2 SSOT) oluşturulduğunda, güncellendiğinde veya silindiğinde
 * otomatik olarak versiyon snapshot'ı oluşturur.
 *
 * Context7 Compliant: ✅
 * SAB: Observer kayıt AppServiceProvider'da comment'li — aktif etmek için
 *      UpsTemplate::observe(IlanTemplateVersionObserver::class) satırını aç.
 */
class IlanTemplateVersionObserver
{
    /**
     * Şablon oluşturulduğunda versiyon kaydı oluştur.
     */
    public function created(UpsTemplate $template): void
    {
        TemplateVersion::create([
            'template_id'         => $template->id,
            'created_by_user_id'  => auth()->id(),
            'version_number'      => 1,
            'snapshot'            => $template->toArray(),
            'change_type'         => 'manual',
            'change_description'  => 'Initial version created',
            'ip_address'          => request()->ip(),
            'user_agent'          => request()->userAgent(),
            'aktiflik_durumu'     => true,
        ]);
    }

    /**
     * Şablon güncellendiğinde versiyon kaydı oluştur.
     */
    public function updated(UpsTemplate $template): void
    {
        if (!$template->isDirty()) {
            return;
        }

        $nextVersion = TemplateVersion::getNextVersionNumber($template->id);

        TemplateVersion::create([
            'template_id'         => $template->id,
            'created_by_user_id'  => auth()->id(),
            'version_number'      => $nextVersion,
            'snapshot'            => $template->toArray(),
            'change_type'         => 'manual',
            'change_description'  => 'Template updated',
            'ip_address'          => request()->ip(),
            'user_agent'          => request()->userAgent(),
            'aktiflik_durumu'     => true,
        ]);
    }

    /**
     * Şablon silindiğinde son durum arşivlenir.
     */
    public function deleted(UpsTemplate $template): void
    {
        $nextVersion = TemplateVersion::getNextVersionNumber($template->id);

        TemplateVersion::create([
            'template_id'         => $template->id,
            'created_by_user_id'  => auth()->id(),
            'version_number'      => $nextVersion,
            'snapshot'            => $template->toArray(),
            'change_type'         => 'manual',
            'change_description'  => 'Template deleted',
            'ip_address'          => request()->ip(),
            'user_agent'          => request()->userAgent(),
            'aktiflik_durumu'     => false,
        ]);
    }
}
