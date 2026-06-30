<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🛡️ Baseline Migration: Core Tables
 * 
 * This migration restores the baseline schema for core entities that were squashed into mysql-schema.sql.
 * It ensures that test environments (SQLite) can bootstrap the database from scratch.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Users Table
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->nullable()->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->bigInteger('ulke_id')->unsigned()->nullable();
                $table->integer('role_id')->nullable();
                $table->string('departman')->nullable();
                $table->string('pozisyon')->nullable();
                $table->json('uzmanlik_alanlari')->nullable();
                $table->string('baslik')->nullable();
                $table->text('bio')->nullable();
                $table->string('instagram_profil')->nullable();
                $table->string('linkedin_profil')->nullable();
                $table->string('website')->nullable();
                $table->string('telefon')->nullable();
                $table->string('ofis_telefon')->nullable();
                $table->text('ofis_adres')->nullable();
                $table->string('whatsapp_numara')->nullable();
                $table->string('telegram_chat_id')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->string('profile_photo_path', 2048)->nullable();
                $table->string('role')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 1.1 Ulkeler Table
        if (!Schema::hasTable('ulkeler')) {
            Schema::create('ulkeler', function (Blueprint $table) {
                $table->id();
                $table->string('ulke_adi');
                $table->string('ulke_kodu', 3)->unique();
                $table->string('telefon_kodu', 10)->nullable();
                $table->string('para_birimi', 3)->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
            });
        }

        // 2. Kisiler Table (CRM SSOT)
        if (!Schema::hasTable('kisiler')) {
            Schema::create('kisiler', function (Blueprint $table) {
                $table->id();
                $table->foreignId('danisman_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ad');
                $table->string('soyad');
                $table->string('email')->nullable();
                $table->string('telefon')->nullable();
                $table->string('telefon_2')->nullable();
                $table->string('tc_kimlik', 11)->nullable();
                $table->text('adres')->nullable();
                $table->foreignId('il_id')->nullable();
                $table->foreignId('ilce_id')->nullable();
                $table->foreignId('mahalle_id')->nullable();
                $table->string('meslek')->nullable();
                $table->string('kisi_tipi')->default('Müşteri');
                $table->string('crm_surec_asamasi')->default('potansiyel');
                $table->boolean('aktiflik_durumu')->default(true);
                $table->text('notlar')->nullable();
                $table->timestamp('last_contacted_at')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users');
                $table->foreignId('ulke_id')->nullable();
                $table->tinyInteger('sesli_onay_verildi')->unsigned()->default(0);
                $table->foreignId('referans_kisi_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Iller Table
        if (!Schema::hasTable('iller')) {
            Schema::create('iller', function (Blueprint $table) {
                $table->id();
                $table->string('il_adi');
                $table->string('plaka_kodu', 3)->unique();
                $table->unsignedBigInteger('api_id')->nullable();
                $table->string('telefon_kodu', 4)->nullable();
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lng', 11, 8)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Ilceler Table
        if (!Schema::hasTable('ilceler')) {
            Schema::create('ilceler', function (Blueprint $table) {
                $table->id();
                $table->foreignId('il_id')->constrained('iller')->cascadeOnDelete();
                $table->string('ilce_adi');
                $table->string('ilce_kodu', 10)->nullable();
                $table->unsignedBigInteger('api_id')->nullable();
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lng', 11, 8)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 5. Mahalleler Table
        if (!Schema::hasTable('mahalleler')) {
            Schema::create('mahalleler', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ilce_id')->constrained('ilceler')->cascadeOnDelete();
                $table->string('mahalle_adi');
                $table->string('mahalle_kodu')->nullable();
                $table->unsignedBigInteger('api_id')->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->string('posta_kodu')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 6. Ilan Kategorileri Table
        if (!Schema::hasTable('ilan_kategorileri')) {
            Schema::create('ilan_kategorileri', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id')->default('SYSTEM');
                $table->string('name');
                $table->string('slug');
                $table->text('aciklama')->nullable();
                $table->bigInteger('parent_id')->unsigned()->nullable();
                $table->string('icon')->nullable();
                $table->integer('display_order')->default(0);
                $table->integer('seviye')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 7. Yayin Tipi Sablonlari Table
        if (!Schema::hasTable('yayin_tipi_sablonlari')) {
            Schema::create('yayin_tipi_sablonlari', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id')->default('SYSTEM');
                $table->string('ad');
                $table->string('slug');
                $table->text('aciklama')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->integer('display_order')->default(0);
                $table->json('varsayilan_ozellikler')->nullable();
                $table->json('fiyat_ayarlari')->nullable();
                $table->foreignId('kategori_id')->nullable()->constrained('ilan_kategorileri')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 8. Ilanlar Table Baseline
        if (!Schema::hasTable('ilanlar')) {
            Schema::create('ilanlar', function (Blueprint $table) {
                $table->id();
                $table->string('baslik');
                $table->string('slug')->unique();
                $table->text('aciklama')->nullable();
                $table->decimal('fiyat', 15, 2)->nullable();
                $table->enum('fiyat_gosterim_modu', ['exact', 'starting_from', 'on_request', 'hidden'])->default('exact');
                $table->decimal('baslangic_fiyati', 15, 2)->nullable();
                $table->string('fiyat_notu')->nullable();
                $table->decimal('purchase_price', 15, 2)->nullable();
                $table->decimal('operating_expenses_annual', 15, 2)->nullable();
                $table->string('investment_currency', 3)->default('TRY');
                $table->decimal('investor_target_roi', 5, 2)->nullable();
                $table->string('para_birimi', 10)->default('TRY');
                
                // Rental Fields
                $table->decimal('gunluk_fiyat', 10, 2)->nullable();
                $table->decimal('haftalik_fiyat', 10, 2)->nullable();
                $table->decimal('aylik_fiyat', 10, 2)->nullable();
                $table->decimal('sezonluk_fiyat', 10, 2)->nullable();
                $table->integer('min_konaklama')->nullable();
                $table->integer('max_misafir')->nullable();
                $table->decimal('temizlik_ucreti', 10, 2)->nullable();
                $table->date('sezon_baslangic')->nullable();
                $table->date('sezon_bitis')->nullable();
                $table->boolean('elektrik_dahil')->default(false);
                $table->boolean('su_dahil')->default(false);
                $table->boolean('havuz')->default(false);
                $table->boolean('havuz_var')->default(false);
                $table->string('havuz_turu')->nullable();
                $table->string('havuz_boyut')->nullable();
                $table->decimal('havuz_derinlik', 5, 2)->nullable();
                
                $table->string('yayin_durumu', 20)->default('taslak');
                $table->tinyInteger('completion_score')->unsigned()->default(0);
                $table->boolean('rental_enabled')->default(false);
                $table->integer('min_stay_nights')->unsigned()->default(1);
                $table->integer('max_stay_nights')->unsigned()->default(30);
                $table->integer('base_guest_count')->default(1);
                $table->decimal('extra_guest_fee', 10, 2)->default(0);
                $table->decimal('security_deposit', 10, 2)->default(0);
                $table->string('booking_type', 50)->default('instant');
                $table->string('cancellation_policy', 50)->default('flexible');
                $table->string('iptal_politikasi', 50)->default('flexible');
                $table->time('checkin_time')->default('14:00:00');
                $table->time('checkout_time')->default('11:00:00');
                $table->decimal('deposit_amount', 12, 2)->nullable();
                $table->string('rental_currency', 3)->default('TRY');
                
                $table->boolean('crm_only')->default(false);
                $table->boolean('firsat_mühru')->default(false);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('ilan_sahibi_id')->nullable();
                $table->integer('minimum_stay')->default(1);
                $table->integer('max_guests')->nullable();
                $table->string('check_in_time', 5)->default('14:00');
                $table->string('check_out_time', 5)->default('11:00');
                $table->string('price_text')->nullable();
                $table->decimal('cleaning_fee', 10, 2)->default(0);
                
                $table->unsignedBigInteger('site_id')->nullable();
                $table->foreignId('danisman_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('ana_kategori_id')->nullable();
                $table->unsignedBigInteger('alt_kategori_id')->nullable();
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->unsignedBigInteger('il_id')->nullable();
                $table->unsignedBigInteger('ilce_id')->nullable();
                $table->unsignedBigInteger('mahalle_id')->nullable();
                $table->string('adres')->nullable();
                
                // Land / Area Fields
                $table->string('ada_no', 50)->nullable();
                $table->string('parsel_no', 50)->nullable();
                $table->string('ada_parsel', 100)->nullable();
                $table->string('imar_statusu', 100)->nullable();
                $table->decimal('kaks', 5, 2)->nullable();
                $table->decimal('taks', 5, 2)->nullable();
                $table->decimal('gabari', 5, 2)->nullable();
                $table->decimal('alan_m2', 12, 2)->nullable();
                $table->decimal('taban_alani', 12, 2)->nullable();
                $table->boolean('yola_cephe')->default(false);
                $table->decimal('yola_cephesi', 8, 2)->nullable();
                $table->boolean('altyapi_elektrik')->default(false);
                $table->boolean('altyapi_su')->default(false);
                $table->boolean('altyapi_dogalgaz')->default(false);
                
                // Structural Fields
                $table->integer('oda_sayisi')->nullable();
                $table->integer('salon_sayisi')->nullable();
                $table->integer('banyo_sayisi')->nullable();
                $table->integer('kat')->nullable();
                $table->integer('toplam_kat')->nullable();
                $table->decimal('brut_m2', 10, 2)->nullable();
                $table->decimal('net_m2', 10, 2)->nullable();
                $table->year('bina_yasi')->nullable();
                $table->string('isitma')->nullable();
                $table->string('aidat')->nullable();
                $table->boolean('esyali')->default(false);
                
                $table->string('ilan_no')->nullable();
                $table->string('referans_no', 50)->nullable()->unique();
                $table->string('dosya_adi')->nullable();
                $table->string('sahibinden_id', 50)->nullable();
                $table->string('emlakjet_id', 50)->nullable();
                $table->string('hepsiemlak_id', 50)->nullable();
                $table->string('zingat_id', 50)->nullable();
                $table->string('hurriyetemlak_id', 50)->nullable();
                
                $table->json('portal_sync_status')->nullable();
                $table->json('metadata')->nullable();
                $table->json('structured_data')->nullable();
                $table->string('structured_data_scope')->nullable();
                $table->string('schema_version')->nullable();
                
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                
                $table->string('rapor_yolu')->nullable();
                $table->string('rapor_hash')->nullable();
                $table->timestamp('rapor_uretildi_at')->nullable();
                $table->unsignedBigInteger('rapor_uretildi_by')->nullable();
                $table->boolean('rapor_gecersiz_mi')->default(false);
                $table->timestamp('rapor_gecersizlestirildi_at')->nullable();
                $table->string('rapor_locale', 5)->nullable();
                $table->string('rapor_surum', 10)->nullable();
                
                $table->integer('visibility_score')->default(0);
                $table->string('country_code', 2)->default('TR');
                $table->string('source_locale', 5)->default('tr_TR');
                
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lng', 11, 8)->nullable();
                $table->unsignedBigInteger('goruntulenme')->default(0);
                
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 9. Roles Table
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // 10. Permissions Table
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // 11. Role has Permissions Table
        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->primary(['permission_id', 'role_id']);
            });
        }

        // 12. Model has Roles Table
        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['role_id', 'model_id', 'model_type']);
                $table->index(['model_id', 'model_type']);
            });
        }

        // 13. Model has Permissions Table
        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['permission_id', 'model_id', 'model_type']);
                $table->index(['model_id', 'model_type']);
            });
        }

        // 17. AI Logs Table (Context7 Standard)
        if (!Schema::hasTable('ai_logs')) {
            Schema::create('ai_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->string('provider', 50);
                $table->string('endpoint', 100);
                $table->string('request_type', 50)->nullable();
                $table->string('event_type', 50)->nullable();
                $table->string('content_type', 50)->nullable();
                $table->bigInteger('content_id')->nullable();
                $table->string('model', 50)->nullable();
                $table->integer('input_tokens')->nullable();
                $table->integer('output_tokens')->nullable();
                $table->integer('total_tokens')->nullable();
                $table->decimal('maliyet_usd', 15, 6)->default(0);
                $table->integer('duration_ms');
                $table->integer('aktiflik_kodu')->default(200);
                $table->string('correlation_id', 64)->nullable();
                $table->string('calisma_durumu', 20)->nullable();
                $table->text('hata_mesaji')->nullable(); // Final renamed column
                $table->foreignId('user_id')->nullable()->constrained('users');
                $table->string('ip_address', 45)->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->json('metadata')->nullable();
                $table->string('version', 10)->nullable();
                $table->timestamp('olusturma_tarihi')->useCurrent();
                $table->timestamp('guncelleme_tarihi')->useCurrent()->useCurrentOnUpdate();
                $table->softDeletes();
            });
        }

        // 15. Leads Table
        if (!Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('email')->nullable();
                $table->string('platform', 50)->nullable();
                $table->string('platform_user_id');
                $table->string('platform_phone', 20)->nullable();
                $table->string('platform_username')->nullable();
                $table->unsignedBigInteger('interested_location_id')->nullable();
                $table->string('interested_property_type')->nullable();
                $table->bigInteger('budget_min')->nullable();
                $table->bigInteger('budget_max')->nullable();
                $table->integer('area_min')->nullable();
                $table->integer('area_max')->nullable();
                $table->string('rooms')->nullable();
                $table->string('intent')->nullable();
                $table->decimal('confidence', 3, 2)->default(0);
                $table->unsignedTinyInteger('quality_score')->default(0);
                $table->enum('temperature', ['cold', 'warm', 'hot'])->default('cold');
                $table->json('entities')->nullable();
                $table->text('first_message')->nullable();
                $table->tinyInteger('crm_durumu')->default(0);
                $table->unsignedBigInteger('assigned_agent_id')->nullable();
                $table->timestamp('last_contacted_at')->nullable();
                $table->timestamp('follow_up_date')->nullable();
                $table->text('notes')->nullable();
                $table->json('tags')->nullable();
                $table->boolean('aktif')->default(true);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('ulke_id')->nullable();
                $table->unsignedBigInteger('ilan_id')->nullable();
                $table->string('interaction_type')->default('message');
                $table->unsignedTinyInteger('sesli_onay_verildi')->default(0);
                $table->timestamps();
            });
        }

        // 16. Lead Activities Table
        if (!Schema::hasTable('lead_activities')) {
            Schema::create('lead_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->string('activity_type');
                $table->text('description')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('activity_date');
                $table->timestamps();
            });
        }

        // 17. AI Feature Usages Table
        if (!Schema::hasTable('ai_feature_usages')) {
            Schema::create('ai_feature_usages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->foreignId('ilan_id')->nullable()->constrained('ilanlar')->nullOnDelete();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->string('feature_slug', 120);
                $table->decimal('confidence', 5, 2)->default(0);
                $table->string('source_tipi', 20)->default('mixed');
                $table->string('provider', 32)->nullable();
                $table->string('aksiyon', 30);
                $table->integer('latency_ms')->nullable();
                $table->boolean('cache_hit')->default(false);
                $table->text('neden')->nullable();
                $table->json('neden_detay')->nullable();
                $table->json('explainability_v2_json')->nullable();
                $table->string('istek_id', 64)->nullable();
                $table->unsignedBigInteger('deney_id')->nullable();
                $table->string('deney_varyasyon_anahtari')->nullable();
                $table->integer('etkilesim_suresi_ms')->nullable();
                $table->decimal('tahmini_tasarruf_sn', 8, 2)->default(0);
                $table->decimal('maliyet_usd', 10, 6)->nullable();
                $table->timestamps();
            });
        }

        // 18. Talepler Table
        if (!Schema::hasTable('talepler')) {
            Schema::create('talepler', function (Blueprint $table) {
                $table->id();
                $table->string('baslik')->nullable();
                $table->text('aciklama')->nullable();
                $table->foreignId('kisi_id')->constrained('kisiler')->cascadeOnDelete();
                $table->foreignId('danisman_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('talep_tipi');
                $table->string('emlak_tipi');
                $table->decimal('min_fiyat', 15, 2)->nullable();
                $table->decimal('max_fiyat', 15, 2)->nullable();
                $table->string('talep_durumu')->default('aktif');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 19. Settings Table
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->string('group')->default('general');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 20. Activity Log Table
        if (!Schema::hasTable('activity_log')) {
            Schema::create('activity_log', function (Blueprint $table) {
                $table->id();
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('event')->nullable();
                $table->string('causer_type')->nullable();
                $table->unsignedBigInteger('causer_id')->nullable();
                $table->json('properties')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->timestamps();
                $table->index(['subject_type', 'subject_id'], 'subject');
                $table->index(['causer_type', 'causer_id'], 'causer');
                $table->index('log_name');
            });
        }

        // 21. Kisi Etkilesimler Table
        if (!Schema::hasTable('kisi_etkilesimler')) {
            Schema::create('kisi_etkilesimler', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kisi_id')->constrained('kisiler')->cascadeOnDelete();
                $table->foreignId('kullanici_id')->constrained('users')->cascadeOnDelete();
                $table->string('tip'); // enum converted to string for baseline simplicity
                $table->text('notlar')->nullable();
                $table->timestamp('etkilesim_tarihi');
                $table->boolean('aktiflik_durumu')->default(true);
                $table->integer('display_order')->default(0);
                $table->foreignId('iliskili_ilan_id')->nullable()->constrained('ilanlar')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 22. Gorevler Table
        if (!Schema::hasTable('gorevler')) {
            Schema::create('gorevler', function (Blueprint $table) {
                $table->id();
                $table->string('baslik');
                $table->text('aciklama')->nullable();
                $table->string('gorev_durumu')->default('beklemede');
                $table->string('oncelik')->default('Normal');
                $table->foreignId('atanan_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('olusturan_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('baslangic_tarihi')->nullable();
                $table->date('bitis_tarihi')->nullable();
                $table->integer('tamamlanma_yuzdesi')->default(0);
                $table->text('notlar')->nullable();
                $table->foreignId('kisi_id')->nullable()->constrained('kisiler')->nullOnDelete();
                $table->foreignId('proje_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 23. Etiketler Table
        if (!Schema::hasTable('etiketler')) {
            Schema::create('etiketler', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('color')->default('#3B82F6');
                $table->text('description')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 28. Feature Categories Table
        if (!Schema::hasTable('feature_categories')) {
            Schema::create('feature_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('applies_to')->nullable();
                $table->string('icon')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 29. Features Table
        if (!Schema::hasTable('features')) {
            Schema::create('features', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('lifecycle')->default('active');
                $table->text('description')->nullable();
                $table->string('type')->default('boolean');
                $table->boolean('aktiflik_durumu')->default(true);
                $table->json('options')->nullable();
                $table->string('unit')->nullable();
                $table->unsignedBigInteger('feature_category_id')->nullable();
                $table->string('applies_to')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_filterable')->default(true);
                $table->boolean('is_searchable')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 30. Feature Assignments Table
        if (!Schema::hasTable('feature_assignments')) {
            Schema::create('feature_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('feature_id');
                $table->string('assignable_type');
                $table->unsignedBigInteger('assignable_id');
                $table->unsignedBigInteger('main_category_id')->nullable();
                $table->unsignedBigInteger('sub_category_id')->nullable();
                $table->unsignedBigInteger('listing_type_id')->nullable();
                $table->string('scope_type', 32)->default('global');
                $table->text('value')->nullable();
                $table->string('label_override')->nullable();
                $table->string('field_slug')->nullable();
                $table->string('field_type', 32)->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_visible')->default(true);
                $table->boolean('is_inherited')->default(false);
                $table->string('origin_category_name')->nullable();
                $table->string('source_type')->default('manual');
                $table->json('metadata')->nullable();
                $table->integer('display_order')->default(0);
                $table->json('conditional_logic')->nullable();
                $table->json('visible_if_json')->nullable();
                $table->json('required_if_json')->nullable();
                $table->json('enabled_if_json')->nullable();
                $table->json('options_json')->nullable();
                $table->timestamp('rolled_back_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->string('group_name', 100)->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
            });
        }

        // 24. AI Deneyler Table
        if (!Schema::hasTable('ai_deneyler')) {
            Schema::create('ai_deneyler', function (Blueprint $table) {
                $table->id();
                $table->string('deney_adi');
                $table->string('deney_slug')->unique();
                $table->string('hedef_kategori')->nullable();
                $table->json('varyasyonlar');
                $table->string('kazanan_varyasyon_anahtari')->nullable();
                $table->timestamp('baslangic_tarihi')->nullable();
                $table->timestamp('bitis_tarihi')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
            });
        }

        // 25. Ledger Accounts Table
        if (!Schema::hasTable('ledger_accounts')) {
            Schema::create('ledger_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type'); // asset, liability, equity, revenue, expense
                $table->string('currency', 3)->default('TRY');
                $table->unsignedBigInteger('ulke_id')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 26. Ledger Balances Table
        if (!Schema::hasTable('ledger_balances')) {
            Schema::create('ledger_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->string('currency', 3)->default('TRY');
                $table->decimal('total_debit', 15, 2)->default(0);
                $table->decimal('total_credit', 15, 2)->default(0);
                $table->decimal('net_balance', 15, 2)->default(0);
                $table->integer('version')->default(1);
                $table->timestamps();
                $table->unique(['account_id', 'currency']);
            });
        }

        // 27. Ledger Entries Table
        if (!Schema::hasTable('ledger_entries')) {
            Schema::create('ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->uuid('transaction_group_id');
                $table->unsignedBigInteger('account_id');
                $table->decimal('debit_amount', 15, 2)->default(0);
                $table->decimal('credit_amount', 15, 2)->default(0);
                $table->string('currency', 3);
                $table->decimal('fx_rate_locked', 10, 6)->nullable();
                $table->decimal('base_amount', 15, 2);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('sebep')->nullable();
                $table->string('kaynak')->default('system');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 34. Ledger Transactions Table
        if (!Schema::hasTable('ledger_transactions')) {
            Schema::create('ledger_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('idempotency_key', 100)->nullable()->unique();
                $table->integer('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 31. Ilan Feature Table
        if (!Schema::hasTable('ilan_feature')) {
            Schema::create('ilan_feature', function (Blueprint $table) {
                $table->foreignId('ilan_id')->constrained('ilanlar')->cascadeOnDelete();
                $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
                $table->string('value')->nullable();
                $table->timestamps();
                $table->primary(['ilan_id', 'feature_id']);
            });
        }

        // 32. Ilan Fotograflari Table
        if (!Schema::hasTable('ilan_fotograflari')) {
            Schema::create('ilan_fotograflari', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ilan_id')->constrained('ilanlar')->cascadeOnDelete();
                $table->string('dosya_adi');
                $table->string('dosya_yolu');
                $table->string('dosya_boyutu')->nullable();
                $table->string('mime_type')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('kapak_mi')->default(false);
                $table->timestamps();
            });
        }

        // 33. Ilan Videolari Table
        if (!Schema::hasTable('ilan_videolari')) {
            Schema::create('ilan_videolari', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ilan_id')->constrained('ilanlar')->cascadeOnDelete();
                $table->string('video_url');
                $table->string('video_tipi')->default('youtube');
                $table->integer('display_order')->default(0);
                $table->timestamps();
            });
        }
        // 35. Governance Decisions Table
        if (!Schema::hasTable('governance_decisions')) {
            Schema::create('governance_decisions', function (Blueprint $table) {
                $table->id();
                $table->string('finding_id')->unique();
                $table->string('source');
                $table->string('domain');
                $table->string('severity');
                $table->string('title');
                $table->text('reason');
                $table->string('target');
                $table->string('recommended_action');
                $table->string('risk');
                $table->string('decision');
                $table->string('karar_durumu')->default('pending');
                $table->unsignedBigInteger('karar_veren_id')->nullable();
                $table->timestamp('karar_tarihi')->nullable();
                $table->text('karar_notu')->nullable();
                $table->string('proposal_filename')->nullable();
                $table->json('meta')->nullable();
                $table->json('explanation')->nullable();
                $table->json('signals')->nullable();
                $table->double('confidence', 3, 2)->nullable();
                $table->json('timeline')->nullable();
                $table->json('rollback_snapshot')->nullable();
                $table->json('action_result')->nullable();
                $table->smallInteger('impact_score')->nullable();
                $table->timestamp('action_completed_at')->nullable();
                $table->string('feedback_note', 500)->nullable();
                $table->string('override_decision')->nullable();
                $table->text('override_reason')->nullable();
                $table->unsignedBigInteger('override_by')->nullable();
                $table->timestamp('override_at')->nullable();
                $table->timestamps();
            });
        }
        // 36. Ozellik Kategorileri
        if (!Schema::hasTable('ozellik_kategorileri')) {
            Schema::create('ozellik_kategorileri', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
            });
        }

        // 37. Ozellikler
        if (!Schema::hasTable('ozellikler')) {
            Schema::create('ozellikler', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('icon')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
            });
        }

        // 38. Notifications
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // 39. Saved Searches
        if (!Schema::hasTable('saved_searches')) {
            Schema::create('saved_searches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->json('filters');
                $table->boolean('notification_enabled')->default(true);
                $table->timestamps();
            });
        }

        // 40. Projeler
        if (!Schema::hasTable('projeler')) {
            Schema::create('projeler', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->foreignId('il_id')->nullable();
                $table->foreignId('ilce_id')->nullable();
                $table->foreignId('mahalle_id')->nullable();
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lng', 11, 8)->nullable();
                $table->string('status')->nullable();
                $table->date('completion_date')->nullable();
                $table->timestamps();
            });
        }

        // 41. Yayin Tipleri
        if (!Schema::hasTable('yayin_tipleri')) {
            Schema::create('yayin_tipleri', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
            });
        }

        // 42. Yazlik Details
        if (!Schema::hasTable('yazlik_details')) {
            Schema::create('yazlik_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ilan_id')->constrained('ilanlar')->cascadeOnDelete();
                $table->integer('yatak_odasi_sayisi')->default(0);
                $table->integer('banyo_sayisi')->default(0);
                $table->integer('max_konaklayan')->default(0);
                $table->boolean('havuz_ozel_mi')->default(false);
                $table->timestamps();
            });
        }

        // 43. Yazlik Fiyatlandirma
        if (!Schema::hasTable('yazlik_fiyatlandirma')) {
            Schema::create('yazlik_fiyatlandirma', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ilan_id')->constrained('ilanlar')->cascadeOnDelete();
                $table->date('baslangic_tarihi');
                $table->date('bitis_tarihi');
                $table->decimal('gunluk_fiyat', 12, 2);
                $table->string('para_birimi', 3)->default('TRY');
                $table->timestamps();
            });
        }

        // 44. Yazlik Rezervasyonlar
        if (!Schema::hasTable('yazlik_rezervasyonlar')) {
            Schema::create('yazlik_rezervasyonlar', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ilan_id')->constrained('ilanlar')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('giris_tarihi');
                $table->date('cikis_tarihi');
                $table->decimal('toplam_tutar', 12, 2);
                $table->string('durum')->default('beklemede');
                $table->timestamps();
            });
        }

        // 45. Yayin Tipi Pivot Atamalari
        if (!Schema::hasTable('yayin_tipi_pivot_atamalari')) {
            Schema::create('yayin_tipi_pivot_atamalari', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('yayin_tipi_id');
                $table->unsignedBigInteger('alt_kategori_id');
                $table->unsignedBigInteger('feature_id');
                $table->boolean('zorunlu_mu')->default(false);
                $table->boolean('gosterim_durumu')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();
            });
        }

        // 46. Ilan Taslaklar
        if (!Schema::hasTable('ilan_taslaklar')) {
            Schema::create('ilan_taslaklar', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('ana_kategori_id')->nullable();
                $table->unsignedBigInteger('alt_kategori_id')->nullable();
                $table->string('baslik')->nullable();
                $table->json('data')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Baseline deletions are risky, typically we don't drop these in production
        // But for test environment parity:
        Schema::dropIfExists('etiketler');
        Schema::dropIfExists('gorevler');
        Schema::dropIfExists('kisi_etkilesimler');
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('talepler');
        Schema::dropIfExists('ai_feature_usages');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('ai_logs');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('ilanlar');
        Schema::dropIfExists('yayin_tipi_sablonlari');
        Schema::dropIfExists('ilan_kategorileri');
        Schema::dropIfExists('mahalleler');
        Schema::dropIfExists('ilceler');
        Schema::dropIfExists('iller');
        Schema::dropIfExists('kisiler');
        Schema::dropIfExists('users');
    }
};
