<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add screenshot to fund_requests (old system)
        Schema::table('fund_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('fund_requests', 'screenshot_path')) {
                $table->string('screenshot_path')->nullable()->after('transaction_id');
            }
        });

        // Add fund_account_id + screenshot to transactions (new system used by FundsController)
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'fund_account_id')) {
                $table->unsignedBigInteger('fund_account_id')->nullable()->after('gateway');
                $table->foreign('fund_account_id')->references('id')->on('fund_accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('transactions', 'screenshot_path')) {
                $table->string('screenshot_path')->nullable()->after('fund_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropColumnIfExists('screenshot_path');
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'fund_account_id')) {
                $table->dropForeign(['fund_account_id']);
                $table->dropColumn('fund_account_id');
            }
            $table->dropColumnIfExists('screenshot_path');
        });
    }
};
