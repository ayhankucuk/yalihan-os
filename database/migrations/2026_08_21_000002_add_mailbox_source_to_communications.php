<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            // source_mailbox: Hangi mailbox/hesaptan geldi (e.g. 'yalihanemlak.com.tr', 'gmail.com/yalihanemlak')
            // Tenant isolation korunur — her tenant kendi mailbox'larini gorur.
            if (! Schema::hasColumn('communications', 'source_mailbox')) {
                $table->string('source_mailbox', 255)
                    ->nullable()
                    ->after('channel')
                    ->comment('Email gelen mailbox: yalihanemlak.com.tr | gmail.com/yalihanemlak | channex | telegram | web');
            }

            // Ekstra: label_ids — Gmail label bilgisi (AIRBNB, BOOKING gibi filter icin)
            if (! Schema::hasColumn('communications', 'gmail_labels')) {
                $table->json('gmail_labels')
                    ->nullable()
                    ->after('platform')
                    ->comment('Gmail label IDs: INBOX, UNREAD, IMPORTANT vb.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            if (Schema::hasColumn('communications', 'source_mailbox')) {
                $table->dropColumn('source_mailbox');
            }
            if (Schema::hasColumn('communications', 'gmail_labels')) {
                $table->dropColumn('gmail_labels');
            }
        });
    }
};
