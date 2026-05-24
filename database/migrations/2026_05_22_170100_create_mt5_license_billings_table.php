<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mt5_license_billings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('account_id', 32);
            $table->string('requested_plan', 20)->default('monthly'); // monthly|permanent
            $table->unsignedInteger('requested_months')->default(1);
            $table->decimal('requested_amount', 14, 2)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mt5_license_billings');
    }
};
