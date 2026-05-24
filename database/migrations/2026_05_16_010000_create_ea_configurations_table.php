<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ea_configurations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('account_id', 32)->unique();

            $table->integer('max_layers')->default(10);
            $table->double('max_accumulative_lot')->default(5.0);
            $table->double('base_lot')->default(0.01);
            $table->double('target_tp_percentage')->default(60.0);

            $table->integer('mart_type')->default(0);
            $table->double('mart_addition')->default(0.01);
            $table->double('mart_multiplier')->default(2.0);

            $table->integer('grid_mode')->default(1);
            $table->integer('fix_grid_distance')->default(50);
            $table->double('atr_multiplier')->default(1.0);
            $table->integer('min_grid_distance')->default(30);

            $table->integer('current_layers')->default(0);
            $table->double('current_accumulative_lot')->default(0.00);
            $table->double('global_floating')->default(0.00);
            $table->string('guard_status')->default('READY');
            $table->boolean('is_online')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ea_configurations');
    }
};
