<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->string('account_currency', 8)->default('USD')->after('base_lot');
        });

        Schema::table('ea_status_reports', function (Blueprint $table): void {
            $table->string('account_currency', 8)->default('USD')->after('guard_status');
        });
    }

    public function down(): void
    {
        Schema::table('ea_status_reports', function (Blueprint $table): void {
            $table->dropColumn('account_currency');
        });

        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->dropColumn('account_currency');
        });
    }
};