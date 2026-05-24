<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->string('pair_symbol', 20)->default('XAUUSD')->after('account_id');
            $table->index('pair_symbol');
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->dropIndex(['pair_symbol']);
            $table->dropColumn('pair_symbol');
        });
    }
};
