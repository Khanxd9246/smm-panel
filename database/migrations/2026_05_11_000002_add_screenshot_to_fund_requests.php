<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds screenshot_path to fund_requests table.
 * Safe to run — uses hasColumn guard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('fund_requests', 'screenshot_path')) {
                $table->string('screenshot_path')->nullable()->after('transaction_id')
                    ->comment('Path in storage/public/fund-proofs/');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            if (Schema::hasColumn('fund_requests', 'screenshot_path')) {
                $table->dropColumn('screenshot_path');
            }
        });
    }
};
