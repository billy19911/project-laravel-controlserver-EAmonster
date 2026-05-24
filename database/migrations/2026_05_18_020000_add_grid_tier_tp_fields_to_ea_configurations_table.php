<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ea_configurations')) {
            return;
        }

        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (!Schema::hasColumn('ea_configurations', 'grid_tp_mode')) {
                $table->unsignedTinyInteger('grid_tp_mode')->default(0)->after('grid_basket_tp_percent');
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_tier1_tp_percent')) {
                $table->double('grid_tier1_tp_percent')->default(60)->after('grid_tp_mode');
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_tier2_tp_percent')) {
                $table->double('grid_tier2_tp_percent')->default(45)->after('grid_tier1_tp_percent');
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_tier3_tp_percent')) {
                $table->double('grid_tier3_tp_percent')->default(30)->after('grid_tier2_tp_percent');
            }
            if (!Schema::hasColumn('ea_configurations', 'grid_tier4_tp_percent')) {
                $table->double('grid_tier4_tp_percent')->default(20)->after('grid_tier3_tp_percent');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ea_configurations')) {
            return;
        }

        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (Schema::hasColumn('ea_configurations', 'grid_tier4_tp_percent')) {
                $table->dropColumn('grid_tier4_tp_percent');
            }
            if (Schema::hasColumn('ea_configurations', 'grid_tier3_tp_percent')) {
                $table->dropColumn('grid_tier3_tp_percent');
            }
            if (Schema::hasColumn('ea_configurations', 'grid_tier2_tp_percent')) {
                $table->dropColumn('grid_tier2_tp_percent');
            }
            if (Schema::hasColumn('ea_configurations', 'grid_tier1_tp_percent')) {
                $table->dropColumn('grid_tier1_tp_percent');
            }
            if (Schema::hasColumn('ea_configurations', 'grid_tp_mode')) {
                $table->dropColumn('grid_tp_mode');
            }
        });
    }
};
