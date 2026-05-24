<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mt5_account_licenses', function (Blueprint $table): void {
            $table->id();
            $table->string('account_id', 32)->unique();
            $table->foreignId('ea_configuration_id')->nullable()->constrained('ea_configurations')->nullOnDelete();
            $table->string('plan_name', 80)->default('Monthly');
            $table->string('status', 20)->default('inactive'); // inactive|active|expired|suspended
            $table->boolean('is_perpetual')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mt5_account_licenses');
    }
};
