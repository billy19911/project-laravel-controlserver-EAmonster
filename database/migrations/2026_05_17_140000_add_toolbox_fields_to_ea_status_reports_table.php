<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_status_reports', function (Blueprint $table): void {
            if (!Schema::hasColumn('ea_status_reports', 'balance')) {
                $table->double('balance')->nullable()->after('guard_status');
            }
            if (!Schema::hasColumn('ea_status_reports', 'equity')) {
                $table->double('equity')->nullable()->after('balance');
            }
            if (!Schema::hasColumn('ea_status_reports', 'open_positions')) {
                $table->json('open_positions')->nullable()->after('equity');
            }
            if (!Schema::hasColumn('ea_status_reports', 'pending_orders')) {
                $table->json('pending_orders')->nullable()->after('open_positions');
            }
            if (!Schema::hasColumn('ea_status_reports', 'closed_trades')) {
                $table->json('closed_trades')->nullable()->after('pending_orders');
            }
            if (!Schema::hasColumn('ea_status_reports', 'wins')) {
                $table->integer('wins')->default(0)->after('closed_trades');
            }
            if (!Schema::hasColumn('ea_status_reports', 'losses')) {
                $table->integer('losses')->default(0)->after('wins');
            }
            if (!Schema::hasColumn('ea_status_reports', 'realized_profit')) {
                $table->double('realized_profit')->default(0)->after('losses');
            }
            if (!Schema::hasColumn('ea_status_reports', 'daily_profit')) {
                $table->double('daily_profit')->default(0)->after('realized_profit');
            }
            if (!Schema::hasColumn('ea_status_reports', 'weekly_profit')) {
                $table->double('weekly_profit')->default(0)->after('daily_profit');
            }
            if (!Schema::hasColumn('ea_status_reports', 'monthly_profit')) {
                $table->double('monthly_profit')->default(0)->after('weekly_profit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ea_status_reports', function (Blueprint $table): void {
            $drop = [];
            foreach (['monthly_profit', 'weekly_profit', 'daily_profit', 'realized_profit', 'losses', 'wins', 'closed_trades', 'pending_orders', 'open_positions', 'equity', 'balance'] as $column) {
                if (Schema::hasColumn('ea_status_reports', $column)) {
                    $drop[] = $column;
                }
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
