<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (!Schema::hasColumn('ea_configurations', 'friday_stop_day')) {
                $table->string('friday_stop_day', 16)->default('friday')->after('use_friday_market_close_window');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            if (Schema::hasColumn('ea_configurations', 'friday_stop_day')) {
                $table->dropColumn('friday_stop_day');
            }
        });
    }
};
