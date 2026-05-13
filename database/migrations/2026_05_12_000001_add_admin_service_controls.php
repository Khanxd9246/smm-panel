<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Admin Service Controls
 *
 * Adds:
 *  - admin_visible          : bool  — admin toggles whether this service appears to users
 *  - admin_price            : decimal — admin override price (null = use provider rate)
 *  - admin_name             : string  — admin custom display name (null = use provider name)
 *  - admin_description      : text    — admin custom description
 *  - delivery_time_label    : string  — human-readable label, e.g. "1–2 hours", "Instant"
 *  - delivery_speed         : enum    — instant | fast | standard | slow  (for filtering/badge)
 *  - estimated_start_min    : int     — minutes before delivery starts (from provider or manual)
 *  - estimated_complete_min : int     — total estimated minutes to complete
 *  - sort_order             : int     — admin-set display order within category
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Visibility — admin decides what users see
            $table->boolean('admin_visible')->default(false)->after('status')
                  ->comment('Only services with admin_visible=true are shown to users');

            // Admin overrides
            $table->decimal('admin_price', 14, 6)->nullable()->after('rate')
                  ->comment('Admin custom sell price per 1000; null = use provider rate');
            $table->string('admin_name', 255)->nullable()->after('name')
                  ->comment('Override display name shown to users');
            $table->text('admin_description')->nullable()->after('description')
                  ->comment('Override description shown to users');

            // Delivery time
            $table->string('delivery_time_label', 80)->nullable()->after('max_time')
                  ->comment('Human-readable label, e.g. "1–3 hours", "Instant (< 1 min)"');
            $table->enum('delivery_speed', ['instant', 'fast', 'standard', 'slow'])
                  ->default('standard')->after('delivery_time_label')
                  ->comment('Speed category for badge display and filtering');
            $table->unsignedInteger('estimated_start_min')->nullable()->after('delivery_speed')
                  ->comment('Estimated minutes before delivery begins');
            $table->unsignedInteger('estimated_complete_min')->nullable()->after('estimated_start_min')
                  ->comment('Estimated total minutes for full completion');

            // Ordering
            $table->unsignedInteger('sort_order')->default(0)->after('admin_visible')
                  ->comment('Admin-controlled display order (lower = first)');

            // Indexes
            $table->index(['admin_visible', 'status'], 'services_admin_visible_status_idx');
            $table->index(['delivery_speed', 'admin_visible'], 'services_delivery_speed_idx');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_admin_visible_status_idx');
            $table->dropIndex('services_delivery_speed_idx');
            $table->dropColumn([
                'admin_visible', 'admin_price', 'admin_name', 'admin_description',
                'delivery_time_label', 'delivery_speed',
                'estimated_start_min', 'estimated_complete_min', 'sort_order',
            ]);
        });
    }
};
