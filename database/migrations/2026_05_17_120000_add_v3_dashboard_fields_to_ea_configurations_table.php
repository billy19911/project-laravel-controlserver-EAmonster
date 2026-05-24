<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (!Schema::hasColumn('ea_configurations', 'grid_tp_points')) {
                $table->integer('grid_tp_points')->default(0)->after('grid_target_usd');
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_sl_points')) {
                $table->integer('grid_sl_points')->default(0)->after('grid_tp_points');
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_use_trailing_layer1')) {
                $table->boolean('grid_use_trailing_layer1')->default(true)->after('grid_sl_points');
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_use_basket_tp_percent')) {
                $table->boolean('grid_use_basket_tp_percent')->default(true)->after('grid_use_trailing_layer1');
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_basket_tp_percent')) {
                $table->double('grid_basket_tp_percent')->default(60.0)->after('grid_use_basket_tp_percent');
            }
            if (!Schema::hasColumn('ea_configurations', 'zero_gap_tp_points')) {
                $table->integer('zero_gap_tp_points')->default(50)->after('mirror_multiplier');
            }
            if (!Schema::hasColumn('ea_configurations', 'zero_gap_sl_points')) {
                $table->integer('zero_gap_sl_points')->default(100)->after('zero_gap_tp_points');
            }
            if (!Schema::hasColumn('ea_configurations', 'zero_gap_max_layers')) {
                $table->unsignedInteger('zero_gap_max_layers')->default(3)->after('zero_gap_sl_points');
            }
            if (!Schema::hasColumn('ea_configurations', 'zero_gap_trailing_start_points')) {
                $table->integer('zero_gap_trailing_start_points')->default(30)->after('zero_gap_max_layers');
            }
            if (!Schema::hasColumn('ea_configurations', 'zero_gap_trailing_step_points')) {
                $table->integer('zero_gap_trailing_step_points')->default(5)->after('zero_gap_trailing_start_points');
            }
            if (!Schema::hasColumn('ea_configurations', 'mart_tp_points')) {
                $table->integer('mart_tp_points')->default(100)->after('mart_addition');
            }
            if (!Schema::hasColumn('ea_configurations', 'mart_sl_points')) {
                $table->integer('mart_sl_points')->default(200)->after('mart_tp_points');
            }
            if (!Schema::hasColumn('ea_configurations', 'mart_trailing_start_points')) {
                $table->integer('mart_trailing_start_points')->default(50)->after('mart_sl_points');
            }
            if (!Schema::hasColumn('ea_configurations', 'mart_trailing_step_points')) {
                $table->integer('mart_trailing_step_points')->default(10)->after('mart_trailing_start_points');
            }
            if (!Schema::hasColumn('ea_configurations', 'use_mirror_trap')) {
                $table->boolean('use_mirror_trap')->default(false)->after('use_pending_guard');
            }
            if (!Schema::hasColumn('ea_configurations', 'use_stealth_mode')) {
                $table->boolean('use_stealth_mode')->default(true)->after('use_mirror_trap');
            }
            if (!Schema::hasColumn('ea_configurations', 'use_asia_session')) {
                $table->boolean('use_asia_session')->default(true)->after('use_stealth_mode');
            }
            if (!Schema::hasColumn('ea_configurations', 'asia_start_wib')) {
                $table->string('asia_start_wib', 5)->default('07:00')->after('use_asia_session');
            }
            if (!Schema::hasColumn('ea_configurations', 'asia_end_wib')) {
                $table->string('asia_end_wib', 5)->default('14:00')->after('asia_start_wib');
            }
            if (!Schema::hasColumn('ea_configurations', 'use_europe_session')) {
                $table->boolean('use_europe_session')->default(true)->after('asia_end_wib');
            }
            if (!Schema::hasColumn('ea_configurations', 'europe_start_wib')) {
                $table->string('europe_start_wib', 5)->default('14:00')->after('use_europe_session');
            }
            if (!Schema::hasColumn('ea_configurations', 'europe_end_wib')) {
                $table->string('europe_end_wib', 5)->default('21:00')->after('europe_start_wib');
            }
            if (!Schema::hasColumn('ea_configurations', 'use_us_session')) {
                $table->boolean('use_us_session')->default(true)->after('europe_end_wib');
            }
            if (!Schema::hasColumn('ea_configurations', 'us_start_wib')) {
                $table->string('us_start_wib', 5)->default('21:00')->after('use_us_session');
            }
            if (!Schema::hasColumn('ea_configurations', 'us_end_wib')) {
                $table->string('us_end_wib', 5)->default('04:00')->after('us_start_wib');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            foreach ([
                'grid_tp_points',
                'grid_sl_points',
                'grid_use_trailing_layer1',
                'grid_use_basket_tp_percent',
                'grid_basket_tp_percent',
                'zero_gap_tp_points',
                'zero_gap_sl_points',
                'zero_gap_max_layers',
                'zero_gap_trailing_start_points',
                'zero_gap_trailing_step_points',
                'mart_tp_points',
                'mart_sl_points',
                'mart_trailing_start_points',
                'mart_trailing_step_points',
                'use_mirror_trap',
                'use_stealth_mode',
                'use_asia_session',
                'asia_start_wib',
                'asia_end_wib',
                'use_europe_session',
                'europe_start_wib',
                'europe_end_wib',
                'use_us_session',
                'us_start_wib',
                'us_end_wib',
            ] as $column) {
                if (Schema::hasColumn('ea_configurations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};