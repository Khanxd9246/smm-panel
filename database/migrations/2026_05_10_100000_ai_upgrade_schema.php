<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * AI Upgrade Migration
 *
 * Adds all new columns required by the AI-powered SMM upgrade.
 * Safe to run on existing databases — all columns use ->nullable() or have defaults.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Services table ────────────────────────────────────────────────
        Schema::table('services', function (Blueprint $table) {
            // Pricing
            if (!Schema::hasColumn('services', 'supplier_rate')) {
                $table->decimal('supplier_rate', 14, 6)->nullable()->after('rate');
            }
            if (!Schema::hasColumn('services', 'custom_margin')) {
                $table->decimal('custom_margin', 8, 4)->nullable()->after('supplier_rate');
            }

            // Quality scoring
            if (!Schema::hasColumn('services', 'quality_score')) {
                $table->unsignedTinyInteger('quality_score')->default(5)->after('max');
            }
            if (!Schema::hasColumn('services', 'quality_status')) {
                $table->enum('quality_status', ['excellent', 'good', 'fair', 'poor'])->default('good')->after('quality_score');
            }
            if (!Schema::hasColumn('services', 'quality_issues')) {
                $table->json('quality_issues')->nullable()->after('quality_status');
            }

            // Statistics
            if (!Schema::hasColumn('services', 'success_rate')) {
                $table->decimal('success_rate', 5, 2)->default(0)->after('quality_issues');
            }
            if (!Schema::hasColumn('services', 'cancel_rate')) {
                $table->decimal('cancel_rate', 5, 2)->default(0)->after('success_rate');
            }
            if (!Schema::hasColumn('services', 'avg_start_time')) {
                $table->unsignedInteger('avg_start_time')->default(0)->after('cancel_rate');
            }

            // Delivery time
            if (!Schema::hasColumn('services', 'estimated_start')) {
                $table->string('estimated_start', 50)->nullable()->after('avg_start_time');
            }
            if (!Schema::hasColumn('services', 'estimated_completion')) {
                $table->string('estimated_completion', 50)->nullable()->after('estimated_start');
            }
            if (!Schema::hasColumn('services', 'delivery_badge')) {
                $table->enum('delivery_badge', ['instant', 'fast', 'standard', 'slow'])->default('standard')->after('estimated_completion');
            }

            // Flags
            if (!Schema::hasColumn('services', 'has_refill')) {
                $table->boolean('has_refill')->default(false)->after('delivery_badge');
            }
            if (!Schema::hasColumn('services', 'is_premium')) {
                $table->boolean('is_premium')->default(false)->after('has_refill');
            }
            if (!Schema::hasColumn('services', 'is_hidden')) {
                $table->boolean('is_hidden')->default(false)->after('is_premium');
            }

            // AI generated content
            if (!Schema::hasColumn('services', 'ai_tags')) {
                $table->json('ai_tags')->nullable()->after('is_hidden');
            }
            if (!Schema::hasColumn('services', 'ai_description')) {
                $table->text('ai_description')->nullable()->after('ai_tags');
            }

            // Analytics
            if (!Schema::hasColumn('services', 'views_count')) {
                $table->unsignedBigInteger('views_count')->default(0)->after('ai_description');
            }
            if (!Schema::hasColumn('services', 'orders_count')) {
                $table->unsignedBigInteger('orders_count')->default(0)->after('views_count');
            }
            if (!Schema::hasColumn('services', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('orders_count');
            }
        });

        // ── Services indexes ──────────────────────────────────────────────
        $this->safeAddIndex('services', ['quality_score'], 'idx_services_quality_score');
        $this->safeAddIndex('services', ['delivery_badge'], 'idx_services_delivery_badge');
        $this->safeAddIndex('services', ['is_hidden', 'status'], 'idx_services_visibility');
        $this->safeAddIndex('services', ['category_id', 'status', 'quality_score'], 'idx_services_category_quality');
        $this->safeAddIndex('services', ['api_service_id'], 'idx_services_api_service_id');
        $this->safeAddIndex('services', ['orders_count'], 'idx_services_orders_count');
        $this->safeAddIndex('services', ['rate'], 'idx_services_rate');

        // ── API Providers table ───────────────────────────────────────────
        Schema::table('api_providers', function (Blueprint $table) {
            if (!Schema::hasColumn('api_providers', 'profit_margin')) {
                $table->decimal('profit_margin', 8, 4)->nullable();
            }
            if (!Schema::hasColumn('api_providers', 'health_score')) {
                $table->unsignedTinyInteger('health_score')->default(5);
            }
            if (!Schema::hasColumn('api_providers', 'health_status')) {
                $table->enum('health_status', ['healthy', 'degraded', 'unstable', 'critical'])->nullable();
            }
            if (!Schema::hasColumn('api_providers', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable();
            }
            if (!Schema::hasColumn('api_providers', 'api_response_ms')) {
                $table->unsignedInteger('api_response_ms')->default(0);
            }
        });

        // ── Categories ────────────────────────────────────────────────────
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'profit_margin')) {
                $table->decimal('profit_margin', 8, 4)->nullable();
            }
        });

        // ── Balance Transactions (wallet audit trail) ─────────────────────
        if (!Schema::hasTable('balance_transactions')) {
            Schema::create('balance_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->enum('type', ['credit', 'debit', 'refund', 'freeze', 'system'])->index();
                $table->decimal('amount', 14, 6);
                $table->decimal('balance_before', 14, 6);
                $table->decimal('balance_after', 14, 6);
                $table->string('reason', 500);
                $table->string('ip_address', 45)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['user_id', 'created_at']);
                $table->index(['admin_id']);
                $table->index(['type', 'created_at']);
            });
        }

        // ── Settings (Table Creation) ─────────────────────────────────────
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('type', 20)->default('string');
                $table->string('group', 50)->default('general');
                $table->timestamps();
            });
        }

        // ── FIX: Force Laravel to forget the previous schema state ───────
        DB::connection()->forgetRecordCursor();

        // ── Seed default settings (using updateOrInsert for reliability) ──
        $defaultSettings = [
            ['key' => 'global_profit_margin', 'value' => '40', 'type' => 'float', 'group' => 'pricing'],
            ['key' => 'ai_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'ai'],
            ['key' => 'auto_hide_low_quality', 'value' => '0', 'type' => 'boolean', 'group' => 'quality'],
            ['key' => 'low_quality_threshold', 'value' => '3', 'type' => 'integer', 'group' => 'quality'],
            ['key' => 'auto_disable_unstable_suppliers', 'value' => '1', 'type' => 'boolean', 'group' => 'suppliers'],
        ];

        foreach ($defaultSettings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('settings');

        Schema::table('services', function (Blueprint $table) {
            $newCols = [
                'supplier_rate', 'custom_margin', 'quality_score', 'quality_status',
                'quality_issues', 'success_rate', 'cancel_rate', 'avg_start_time',
                'estimated_start', 'estimated_completion', 'delivery_badge',
                'has_refill', 'is_premium', 'is_hidden', 'ai_tags', 'ai_description',
                'views_count', 'orders_count', 'last_synced_at',
            ];
            foreach ($newCols as $col) {
                if (Schema::hasColumn('services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('api_providers', function (Blueprint $table) {
            $apiCols = ['profit_margin', 'health_score', 'health_status', 'last_checked_at', 'api_response_ms'];
            foreach ($apiCols as $col) {
                if (Schema::hasColumn('api_providers', $col)) $table->dropColumn($col);
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'profit_margin')) {
                $table->dropColumn('profit_margin');
            }
        });
    }

    private function safeAddIndex(string $table, array|string $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                $t->index($columns, $name);
            });
        } catch (\Exception $e) {
            // Index likely already exists
        }
    }
};
