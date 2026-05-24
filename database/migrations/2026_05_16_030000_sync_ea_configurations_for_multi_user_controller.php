<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (!Schema::hasColumn('ea_configurations', 'active_strategy')) {
                $table->unsignedTinyInteger('active_strategy')->default(0);
            }
            if (!Schema::hasColumn('ea_configurations', 'timeframe_logic')) {
                $table->unsignedInteger('timeframe_logic')->default(1);
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_max_layers')) {
                $table->unsignedInteger('grid_max_layers')->default(10);
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_max_accumulative_lot')) {
                $table->double('grid_max_accumulative_lot')->default(5.0);
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_target_usd')) {
                $table->double('grid_target_usd')->default(10.0);
            }
            if (!Schema::hasColumn('ea_configurations', 'mirror_active')) {
                $table->boolean('mirror_active')->default(true);
            }
            if (!Schema::hasColumn('ea_configurations', 'mirror_pending_distance_points')) {
                $table->unsignedInteger('mirror_pending_distance_points')->default(50);
            }
            if (!Schema::hasColumn('ea_configurations', 'mirror_multiplier')) {
                $table->double('mirror_multiplier')->default(2.0);
            }
            if (!Schema::hasColumn('ea_configurations', 'mart_max_steps')) {
                $table->unsignedInteger('mart_max_steps')->default(7);
            }
            if (!Schema::hasColumn('ea_configurations', 'filter_snr_activation')) {
                $table->boolean('filter_snr_activation')->default(true);
            }
            if (!Schema::hasColumn('ea_configurations', 'news_filter_severity')) {
                $table->string('news_filter_severity', 16)->default('HIGH');
            }
            if (!Schema::hasColumn('ea_configurations', 'news_pause_before_minutes')) {
                $table->unsignedInteger('news_pause_before_minutes')->default(15);
            }
            if (!Schema::hasColumn('ea_configurations', 'news_pause_after_minutes')) {
                $table->unsignedInteger('news_pause_after_minutes')->default(15);
            }
            if (!Schema::hasColumn('ea_configurations', 'live_floating_pnl')) {
                $table->double('live_floating_pnl')->default(0);
            }
            if (!Schema::hasColumn('ea_configurations', 'live_open_layers')) {
                $table->unsignedInteger('live_open_layers')->default(0);
            }
            if (!Schema::hasColumn('ea_configurations', 'live_guard_status')) {
                $table->string('live_guard_status', 32)->default('LIVE');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            foreach ([
                'active_strategy',
                'timeframe_logic',
                'grid_max_layers',
                'grid_max_accumulative_lot',
                'grid_target_usd',
                'mirror_active',
                'mirror_pending_distance_points',
                'mirror_multiplier',
                'mart_max_steps',
                'filter_snr_activation',
                'news_filter_severity',
                'news_pause_before_minutes',
                'news_pause_after_minutes',
                'live_floating_pnl',
                'live_open_layers',
                'live_guard_status',
            ] as $column) {
                if (Schema::hasColumn('ea_configurations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
