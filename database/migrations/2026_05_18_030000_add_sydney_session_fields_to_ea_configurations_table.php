<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (!Schema::hasColumn('ea_configurations', 'use_sydney_session')) {
                $table->boolean('use_sydney_session')->default(true)->after('use_stealth_mode');
            }
            if (!Schema::hasColumn('ea_configurations', 'sydney_start_wib')) {
                $table->string('sydney_start_wib', 5)->default('05:00')->after('use_sydney_session');
            }
            if (!Schema::hasColumn('ea_configurations', 'sydney_end_wib')) {
                $table->string('sydney_end_wib', 5)->default('14:00')->after('sydney_start_wib');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            foreach ([
                'use_sydney_session',
                'sydney_start_wib',
                'sydney_end_wib',
            ] as $column) {
                if (Schema::hasColumn('ea_configurations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
