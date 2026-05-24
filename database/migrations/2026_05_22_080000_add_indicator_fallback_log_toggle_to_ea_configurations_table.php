<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (!Schema::hasColumn('ea_configurations', 'show_indicator_fallback_logs')) {
                $table->boolean('show_indicator_fallback_logs')
                    ->default(false)
                    ->after('use_stealth_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (Schema::hasColumn('ea_configurations', 'show_indicator_fallback_logs')) {
                $table->dropColumn('show_indicator_fallback_logs');
            }
        });
    }
};
