<?php

namespace Database\Seeders\GuestCommunication;

use App\Models\Notification\NotificationTemplate;
use Illuminate\Database\Seeder;

/**
 * GuestWelcomeTemplatesSeeder
 *
 * GuestCommunication WAVE 1
 *
 * Welcome message templates for TR, EN, AR languages.
 * Seeds into notification_templates table.
 */
class GuestWelcomeTemplatesSeeder extends Seeder
{
    /**
     * Template keys for welcome messages
     */
    private const WELCOME_TEMPLATES = [
        'tr' => [
            'key' => 'guest.welcome.tr',
            'subject' => 'Hoş Geldiniz! {{ property_title }}',
            'content' => <<<'EOT'
Merhaba {{ guest_first_name }},

Rezervasyonunuz onaylandı! {{ property_title }} tesislerinde sizi ağırlamaktan memnuniyet duyacağız.

📅 Rezervasyon Detayları:
• Giriş: {{ check_in_date }} ({{ check_in_time }})
• Çıkış: {{ check_out_date }} ({{ check_out_time }})
• Gece Sayısı: {{ nights }}
• Kişi Sayısı: {{ guest_count }}

📍 Adres:
{{ property_address }}

Ev kuralları ve detaylı bilgiler için {{ property_title }} sayfasını ziyaret edebilirsiniz.

Herhangi bir sorunuz varsa bizimle iletişime geçmekten çekinmeyin.

İyi tatiller dileriz!
EOT,
        ],
        'en' => [
            'key' => 'guest.welcome.en',
            'subject' => 'Welcome! {{ property_title }}',
            'content' => <<<'EOT'
Hello {{ guest_first_name }},

Your reservation is confirmed! We are delighted to host you at {{ property_title }}.

📅 Reservation Details:
• Check-in: {{ check_in_date }} ({{ check_in_time }})
• Check-out: {{ check_out_date }} ({{ check_out_time }})
• Nights: {{ nights }}
• Guests: {{ guest_count }}

📍 Address:
{{ property_address }}

Please visit the {{ property_title }} page for house rules and detailed information.

Don't hesitate to contact us if you have any questions.

Wishing you a wonderful stay!
EOT,
        ],
        'ar' => [
            'key' => 'guest.welcome.ar',
            'subject' => '!مرحباً بك في {{ property_title }}',
            'content' => <<<'EOT'
مرحباً {{ guest_first_name }},

تم تأكيد حجزك! يسعدنا استضافتك في {{ property_title }}.

📅 تفاصيل الحجز:
• تسجيل الوصول: {{ check_in_date }} ({{ check_in_time }})
• تسجيل المغادرة: {{ check_out_date }} ({{ check_out_time }})
• عدد الليالي: {{ nights }}
• عدد الضيوف: {{ guest_count }}

📍 العنوان:
{{ property_address }}

يرجى زيارة صفحة {{ property_title }} للاطلاع على قواعد المنزل والمعلومات التفصيلية.

لا تتردد في الاتصال بنا إذا كان لديك أي أسئلة.

نتمنى لك إقامة سعيدة!
EOT,
        ],
    ];

    /**
     * Run the seeder.
     */
    public function run(): void
    {
        foreach (self::WELCOME_TEMPLATES as $language => $template) {
            $this->createOrUpdateTemplate($template['key'], $template['subject'], $template['content'], $language);
        }
    }

    /**
     * Create or update a template
     */
    private function createOrUpdateTemplate(string $key, string $subject, string $content, string $language): void
    {
        NotificationTemplate::updateOrCreate(
            ['key' => $key],
            [
                'channel' => 'airbnb', // Default channel
                'subject' => $subject,
                'content' => $content,
                'language' => $language,
                'display_order' => 1,
                'aktiflik_durumu' => 1, // Active
                'provider_template_id' => null,
                'metadata' => [
                    'type' => 'guest_welcome',
                    'created_by' => 'system',
                    'version' => '1.0',
                ],
            ]
        );

        $this->command->info("✓ Template created/updated: {$key}");
    }
}
