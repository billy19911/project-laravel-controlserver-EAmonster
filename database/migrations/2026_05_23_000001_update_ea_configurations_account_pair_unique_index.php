<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            // Legacy schema used unique(account_id). Drop it so one MT5 account can map to multiple pairs.
            try {
                $table->dropUnique('ea_configurations_account_id_unique');
            } catch (\Throwable) {
                // Ignore when index does not exist (different DB engine/name).
            }

            try {
                $table->unique(['account_id', 'pair_symbol'], 'ea_configurations_account_pair_unique');
            } catch (\Throwable) {
                // Ignore if already created.
            }
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            try {
                $table->dropUnique('ea_configurations_account_pair_unique');
            } catch (\Throwable) {
                // Ignore when index does not exist.
            }

            try {
                $table->unique('account_id');
            } catch (\Throwable) {
                // Ignore when legacy unique already restored.
            }
        });
    }
};
