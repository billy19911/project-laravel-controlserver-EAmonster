<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ea_status_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ea_configuration_id')->constrained('ea_configurations')->cascadeOnDelete();
            $table->string('account_id', 32)->index();
            $table->integer('current_layers')->default(0);
            $table->double('current_accumulative_lot')->default(0.0);
            $table->double('global_floating')->default(0.0);
            $table->string('guard_status', 32)->default('READY');
            $table->timestamps();

            $table->index(['user_id', 'account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ea_status_reports');
    }
};
