<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (!Schema::hasColumn('ea_configurations', 'use_us10y_filter')) {
                $table->boolean('use_us10y_filter')->default(false);
            }

            if (!Schema::hasColumn('ea_configurations', 'use_vix_filter')) {
                $table->boolean('use_vix_filter')->default(false);
            }

            if (!Schema::hasColumn('ea_configurations', 'use_oil_filter')) {
                $table->boolean('use_oil_filter')->default(false);
            }

            if (!Schema::hasColumn('ea_configurations', 'use_friday_market_close_window')) {
                $table->boolean('use_friday_market_close_window')->default(false);
            }

            if (!Schema::hasColumn('ea_configurations', 'friday_stop_wib')) {
                $table->string('friday_stop_wib', 5)->default('23:45');
            }

            if (!Schema::hasColumn('ea_configurations', 'friday_resume_wib')) {
                $table->string('friday_resume_wib', 5)->default('06:15');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            foreach ([
                'use_us10y_filter',
                'use_vix_filter',
                'use_oil_filter',
                'use_friday_market_close_window',
                'friday_stop_wib',
                'friday_resume_wib',
            ] as $column) {
                if (Schema::hasColumn('ea_configurations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
