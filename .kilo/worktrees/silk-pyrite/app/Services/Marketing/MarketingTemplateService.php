<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Storage;

class MarketingTemplateService
{
    /**
     * Save a new or updated marketing template.
     */
    public function saveTemplate(string $format, string $templateName, array $templateData): void
    {
        $templatePath = "marketing/templates/{$format}/{$templateName}.json";
        Storage::put($templatePath, json_encode($templateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Delete an existing marketing template.
     */
    public function deleteTemplate(string $format, string $templateName): bool
    {
        $templatePath = "marketing/templates/{$format}/{$templateName}.json";
        
        if (Storage::exists($templatePath)) {
            return Storage::delete($templatePath);
        }

        return false;
    }
}
