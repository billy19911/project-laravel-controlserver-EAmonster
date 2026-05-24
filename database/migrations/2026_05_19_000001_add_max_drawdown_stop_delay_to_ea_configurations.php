<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            // Max drawdown stop delay in seconds (0 = instant, default 300 = 5 minutes)
            $table->integer('max_drawdown_stop_delay')->default(0)->after('max_drawdown_pct');
            $table->index('max_drawdown_stop_delay');
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->dropIndex(['max_drawdown_stop_delay']);
            $table->dropColumn('max_drawdown_stop_delay');
        });
    }
};
