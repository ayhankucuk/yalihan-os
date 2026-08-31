<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5.3: Advisor Photo Intelligence Schema Alignment
 *
 * Adds photo analysis, quality scoring, auto-ordering and metadata columns
 * to advisor_photos table idempotently.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('advisor_photos')) {
            Schema::table('advisor_photos', function (Blueprint $table) {
                if (Schema::hasColumn('advisor_photos', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                }
                if (!Schema::hasColumn('advisor_photos', 'filename')) {
                    $table->string('filename')->nullable()->after('path');
                }
                if (!Schema::hasColumn('advisor_photos', 'mime_type')) {
                    $table->string('mime_type', 100)->nullable()->after('filename');
                }
                if (!Schema::hasColumn('advisor_photos', 'width')) {
                    $table->integer('width')->nullable()->after('mime_type');
                }
                if (!Schema::hasColumn('advisor_photos', 'height')) {
                    $table->integer('height')->nullable()->after('width');
                }
                if (!Schema::hasColumn('advisor_photos', 'file_size')) {
                    $table->unsignedBigInteger('file_size')->nullable()->after('height');
                }
                if (!Schema::hasColumn('advisor_photos', 'quality_score')) {
                    $table->decimal('quality_score', 5, 2)->nullable()->after('file_size');
                }
                if (!Schema::hasColumn('advisor_photos', 'quality_metrics')) {
                    $table->json('quality_metrics')->nullable()->after('quality_score');
                }
                if (!Schema::hasColumn('advisor_photos', 'analysis_details')) {
                    $table->json('analysis_details')->nullable()->after('quality_metrics');
                }
                if (!Schema::hasColumn('advisor_photos', 'improvement_suggestions')) {
                    $table->json('improvement_suggestions')->nullable()->after('analysis_details');
                }
                if (!Schema::hasColumn('advisor_photos', 'visual_keywords')) {
                    $table->json('visual_keywords')->nullable()->after('improvement_suggestions');
                }
                if (!Schema::hasColumn('advisor_photos', 'featured')) {
                    $table->boolean('featured')->default(false)->after('display_order');
                }
                if (!Schema::hasColumn('advisor_photos', 'featured_at')) {
                    $table->timestamp('featured_at')->nullable()->after('featured');
                }
                if (!Schema::hasColumn('advisor_photos', 'analyzed_at')) {
                    $table->timestamp('analyzed_at')->nullable()->after('featured_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('advisor_photos')) {
            Schema::table('advisor_photos', function (Blueprint $table) {
                $columnsToDrop = [];
                $columns = [
                    'filename',
                    'mime_type',
                    'width',
                    'height',
                    'file_size',
                    'quality_score',
                    'quality_metrics',
                    'analysis_details',
                    'improvement_suggestions',
                    'visual_keywords',
                    'featured',
                    'featured_at',
                    'analyzed_at',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('advisor_photos', $column)) {
                        $columnsToDrop[] = $column;
                    }
                }

                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
