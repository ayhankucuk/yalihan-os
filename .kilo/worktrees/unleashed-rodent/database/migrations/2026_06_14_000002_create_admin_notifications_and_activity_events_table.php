<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B-006 P5B — AdminNotification + AdminActivityEvent kanonik tablolar
 *
 * AdminNotification  : Danışman bazlı bildirim kaydı (rezervasyon, takvim vs.)
 * AdminActivityEvent : Telegram ↔ Admin UI activity feed (read-only log)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // admin_notifications
        // ---------------------------------------------------------------
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('channel', 50)->comment('reservation, calendar, system');
            $table->string('event', 100)->comment('reservation_created, reservation_cancelled, ...');
            $table->string('title', 255);
            $table->text('message');
            $table->json('payload')->nullable();
            $table->boolean('is_read')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'is_read'], 'admin_notif_user_read_idx');
            $table->index(['user_id', 'created_at'], 'admin_notif_user_created_idx');
        });

        // ---------------------------------------------------------------
        // admin_activity_events
        // ---------------------------------------------------------------
        Schema::create('admin_activity_events', function (Blueprint $table) {
            $table->id();

            $table->string('entity_type', 50)->comment('reservation, calendar, ...');
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 50)->comment('create, confirm, cancel, close_calendar, ...');
            $table->string('source', 50)->comment('admin, telegram, system');
            $table->string('summary', 500)->nullable();
            $table->json('context')->nullable();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->unsignedBigInteger('telegram_user_id')->nullable();

            $table->timestamps();

            $table->index(['entity_type', 'entity_id'], 'admin_act_entity_idx');
            $table->index(['action', 'source'], 'admin_act_action_source_idx');
            $table->index('created_at', 'admin_act_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_events');
        Schema::dropIfExists('admin_notifications');
    }
};
