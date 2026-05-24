<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->integer('max_spread')->default(500)->after('base_lot');
            $table->double('max_drawdown_pct')->default(5.0)->after('max_spread');
            $table->double('daily_profit_target')->default(0.0)->after('max_drawdown_pct');
            $table->boolean('use_martingale')->default(false)->after('daily_profit_target');
            $table->integer('atr_period_grid')->default(14)->after('fix_grid_distance');
            $table->integer('atr_timeframe_grid')->default(1)->after('atr_period_grid');
            $table->double('farming_gap')->default(2.0)->after('min_grid_distance');
            $table->integer('mart_start_layer')->default(2)->after('farming_gap');
            $table->double('initial_sl')->default(6.0)->after('mart_start_layer');
            $table->double('trail_start')->default(5.0)->after('initial_sl');
            $table->double('trail_stop')->default(1.3)->after('trail_start');
            $table->double('trail_step')->default(0.1)->after('trail_stop');
            $table->boolean('use_breakeven')->default(false)->after('trail_step');
            $table->double('be_distance')->default(2.0)->after('use_breakeven');
            $table->double('be_buffer')->default(0.01)->after('be_distance');
            $table->integer('start_hour')->default(0)->after('be_buffer');
            $table->integer('end_hour')->default(23)->after('start_hour');
            $table->boolean('always_in_market')->default(false)->after('end_hour');
            $table->boolean('instant_reentry')->default(false)->after('always_in_market');
            $table->boolean('auto_flip')->default(false)->after('instant_reentry');
            $table->boolean('use_pending_guard')->default(false)->after('auto_flip');
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->dropColumn([
                'max_spread',
                'max_drawdown_pct',
                'daily_profit_target',
                'use_martingale',
                'atr_period_grid',
                'atr_timeframe_grid',
                'farming_gap',
                'mart_start_layer',
                'initial_sl',
                'trail_start',
                'trail_stop',
                'trail_step',
                'use_breakeven',
                'be_distance',
                'be_buffer',
                'start_hour',
                'end_hour',
                'always_in_market',
                'instant_reentry',
                'auto_flip',
                'use_pending_guard',
            ]);
        });
    }
};
