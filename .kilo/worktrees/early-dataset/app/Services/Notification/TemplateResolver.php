<?php

namespace App\Services\Notification;

use App\Models\Notification\NotificationTemplate;
use Illuminate\Support\Facades\Log;

class TemplateResolver
{
    /**
     * Resolve template content with variables.
     */
    public function resolve(string $key, string $channel, array $data = [], string $language = 'tr'): array
    {
        $template = NotificationTemplate::active()
            ->where('key', $key)
            ->where('channel', $channel)
            ->where('language', $language)
            ->first();

        if (!$template) {
            Log::warning("TemplateResolver: Template not found for key '{$key}' on channel '{$channel}' ({$language})");
            return [
                'subject' => $data['subject'] ?? null,
                'body' => $data['body'] ?? $data['message'] ?? '',
                'provider_template_id' => null
            ];
        }

        return [
            'subject' => $this->injectVariables($template->subject ?? '', $data),
            'body' => $this->injectVariables($template->content ?? '', $data),
            'provider_template_id' => $template->provider_template_id,
            'metadata' => $template->metadata
        ];
    }

    /**
     * Inject variables into a string using {{variable}} syntax.
     */
    protected function injectVariables(?string $text, array $data): string
    {
        if (empty($text)) {
            return '';
        }

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9._]+)\s*\}\}/', function ($matches) use ($data) {
            $key = $matches[1];
            
            // Support dot notation for nested data
            $value = data_get($data, $key);

            return $value !== null ? (string)$value : $matches[0];
        }, $text);
    }
}
