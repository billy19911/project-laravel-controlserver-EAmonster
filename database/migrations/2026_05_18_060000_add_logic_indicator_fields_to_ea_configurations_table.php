<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ea_configurations')) {
            return;
        }

        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (!Schema::hasColumn('ea_configurations', 'bb_period')) {
                $table->unsignedInteger('bb_period')->default(20);
            }
            if (!Schema::hasColumn('ea_configurations', 'bb_deviation')) {
                $table->decimal('bb_deviation', 8, 2)->default(2.00);
            }
            if (!Schema::hasColumn('ea_configurations', 'rsi_period')) {
                $table->unsignedInteger('rsi_period')->default(14);
            }
            if (!Schema::hasColumn('ea_configurations', 'rsi_buy_level')) {
                $table->decimal('rsi_buy_level', 8, 2)->default(45.00);
            }
            if (!Schema::hasColumn('ea_configurations', 'rsi_sell_level')) {
                $table->decimal('rsi_sell_level', 8, 2)->default(55.00);
            }
            if (!Schema::hasColumn('ea_configurations', 'adx_period')) {
                $table->unsignedInteger('adx_period')->default(14);
            }
            if (!Schema::hasColumn('ea_configurations', 'adx_level')) {
                $table->decimal('adx_level', 8, 2)->default(25.00);
            }
            if (!Schema::hasColumn('ea_configurations', 'adx_bars')) {
                $table->unsignedInteger('adx_bars')->default(3);
            }
            if (!Schema::hasColumn('ea_configurations', 'adx_sideways')) {
                $table->decimal('adx_sideways', 8, 2)->default(18.00);
            }
            if (!Schema::hasColumn('ea_configurations', 'ema_period')) {
                $table->unsignedInteger('ema_period')->default(50);
            }
            if (!Schema::hasColumn('ea_configurations', 'ema_fast')) {
                $table->unsignedInteger('ema_fast')->default(20);
            }
            if (!Schema::hasColumn('ea_configurations', 'ema_slow')) {
                $table->unsignedInteger('ema_slow')->default(50);
            }
            if (!Schema::hasColumn('ea_configurations', 'ema_slope_min')) {
                $table->decimal('ema_slope_min', 8, 4)->default(0.0300);
            }
            if (!Schema::hasColumn('ea_configurations', 'atr_period')) {
                $table->unsignedInteger('atr_period')->default(14);
            }
            if (!Schema::hasColumn('ea_configurations', 'use_dxy_filter')) {
                $table->boolean('use_dxy_filter')->default(false);
            }
            if (!Schema::hasColumn('ea_configurations', 'close_all_on_news')) {
                $table->boolean('close_all_on_news')->default(false);
            }
            if (!Schema::hasColumn('ea_configurations', 'use_trend_filter')) {
                $table->boolean('use_trend_filter')->default(false);
            }
            if (!Schema::hasColumn('ea_configurations', 'use_ai_core_sharpening')) {
                $table->boolean('use_ai_core_sharpening')->default(false);
            }
            if (!Schema::hasColumn('ea_configurations', 'use_ema_ribbon')) {
                $table->boolean('use_ema_ribbon')->default(true);
            }
            if (!Schema::hasColumn('ea_configurations', 'use_dmi')) {
                $table->boolean('use_dmi')->default(true);
            }
            if (!Schema::hasColumn('ea_configurations', 'use_mkt_struct')) {
                $table->boolean('use_mkt_struct')->default(true);
            }
            if (!Schema::hasColumn('ea_configurations', 'use_early_trend')) {
                $table->boolean('use_early_trend')->default(true);
            }
            if (!Schema::hasColumn('ea_configurations', 'use_sniper_entry')) {
                $table->boolean('use_sniper_entry')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ea_configurations')) {
            return;
        }

        Schema::table('ea_configurations', function (Blueprint $table): void {
            $columns = [
                'bb_period',
                'bb_deviation',
                'rsi_period',
                'rsi_buy_level',
                'rsi_sell_level',
                'adx_period',
                'adx_level',
                'adx_bars',
                'adx_sideways',
                'ema_period',
                'ema_fast',
                'ema_slow',
                'ema_slope_min',
                'atr_period',
                'use_dxy_filter',
                'close_all_on_news',
                'use_trend_filter',
                'use_ai_core_sharpening',
                'use_ema_ribbon',
                'use_dmi',
                'use_mkt_struct',
                'use_early_trend',
                'use_sniper_entry',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('ea_configurations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
