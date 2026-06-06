<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->double('current_balance')->default(0.0)->after('global_floating');
            $table->double('current_equity')->default(0.0)->after('current_balance');
            $table->double('today_pnl')->default(0.0)->after('current_equity');
            $table->double('drawdown_pct')->default(0.0)->after('today_pnl');
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->dropColumn([
                'current_balance',
                'current_equity',
                'today_pnl',
                'drawdown_pct',
            ]);
        });
    }
};
