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
 *
 * Adds:
 *  - services: quality scoring, delivery time, AI fields, supplier rate, pricing
 *  - api_providers: health monitoring fields
 *  - categories: profit_margin
 *  - balance_transactions: new table for wallet audit trail
 *  - settings: key/value config store (if not exists)
 *  - Indexes for performance
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Services table ────────────────────────────────────────────────
        Schema::table('services', function (Blueprint $table) {
            // Pricing
            if (!Schema::hasColumn('services', 'supplier_rate')) {
                $table->decimal('supplier_rate', 14, 6)->nullable()->after('rate')
                    ->comment('Raw supplier cost before margin');
            }
            if (!Schema::hasColumn('services', 'custom_margin')) {
                $table->decimal('custom_margin', 8, 4)->nullable()->after('supplier_rate')
                    ->comment('Override margin % for this service');
            }

            // Quality scoring
            if (!Schema::hasColumn('services', 'quality_score')) {
                $table->unsignedTinyInteger('quality_score')->default(5)->after('max')
                    ->comment('1-10 quality score from ServiceQualityService');
            }
            if (!Schema::hasColumn('services', 'quality_status')) {
                $table->enum('quality_status', ['excellent', 'good', 'fair', 'poor'])->default('good')->after('quality_score');
            }
            if (!Schema::hasColumn('services', 'quality_issues')) {
                $table->json('quality_issues')->nullable()->after('quality_status');
            }

            // Real statistics
            if (!Schema::hasColumn('services', 'success_rate')) {
                $table->decimal('success_rate', 5, 2)->default(0)->after('quality_issues');
            }
            if (!Schema::hasColumn('services', 'cancel_rate')) {
                $table->decimal('cancel_rate', 5, 2)->default(0)->after('success_rate');
            }
            if (!Schema::hasColumn('services', 'avg_start_time')) {
                $table->unsignedInteger('avg_start_time')->default(0)->after('cancel_rate')
                    ->comment('Average minutes until order starts');
            }

            // Delivery time display
            if (!Schema::hasColumn('services', 'estimated_start')) {
                $table->string('estimated_start', 50)->nullable()->after('avg_start_time')
                    ->comment('Human readable e.g. "0-15 mins"');
            }
            if (!Schema::hasColumn('services', 'estimated_completion')) {
                $table->string('estimated_completion', 50)->nullable()->after('estimated_start')
                    ->comment('Human readable e.g. "1-3 hours"');
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
                $table->decimal('profit_margin', 8, 4)->nullable()
                    ->comment('Provider-level default profit margin %');
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

        // ── Categories profit margin ──────────────────────────────────────
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'profit_margin')) {
                $table->decimal('profit_margin', 8, 4)->nullable()
                    ->comment('Category-level default profit margin %');
            }
        });

        // ── Balance Transactions (wallet audit trail) ─────────────────────
        if (!Schema::hasTable('balance_transactions')) {
            Schema::create('balance_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('admin_id')->nullable()
                    ->comment('Admin who performed the action (null for system)');
                $table->enum('type', ['credit', 'debit', 'refund', 'freeze', 'system'])
                    ->index();
                $table->decimal('amount', 14, 6);
                $table->decimal('balance_before', 14, 6);
                $table->decimal('balance_after', 14, 6);
                $table->string('reason', 500);
                $table->string('ip_address', 45)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable()
                    ->comment('Related order/transaction ID');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['user_id', 'created_at']);
                $table->index(['admin_id']);
                $table->index(['type', 'created_at']);
            });
        }

        // ── Settings (key/value store) ────────────────────────────────────
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('type', 20)->default('string')
                    ->comment('string|integer|float|boolean|json');
                $table->string('group', 50)->default('general');
                $table->timestamps();
            });
        }

        // ── Seed default settings ─────────────────────────────────────────
        DB::table('settings')->upsert([
            ['key' => 'global_profit_margin', 'value' => '40', 'type' => 'float', 'group' => 'pricing', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ai_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'ai', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'auto_hide_low_quality', 'value' => '0', 'type' => 'boolean', 'group' => 'quality', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'low_quality_threshold', 'value' => '3', 'type' => 'integer', 'group' => 'quality', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'auto_disable_unstable_suppliers', 'value' => '1', 'type' => 'boolean', 'group' => 'suppliers', 'created_at' => now(), 'updated_at' => now()],
        ], ['key'], ['value', 'updated_at']);
    }

    public function down(): void
    {
        // Drop new tables
        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('settings');

        // Remove new columns from services
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
            foreach (['profit_margin', 'health_score', 'health_status', 'last_checked_at', 'api_response_ms'] as $col) {
                if (Schema::hasColumn('api_providers', $col)) $table->dropColumn($col);
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'profit_margin')) {
                $table->dropColumn('profit_margin');
            }
        });
    }

    /**
     * Safely add an index without failing if it already exists.
     */
    private function safeAddIndex(string $table, array|string $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                $t->index($columns, $name);
            });
        } catch (\Exception) {
            // Index already exists — skip
        }
    }
};
