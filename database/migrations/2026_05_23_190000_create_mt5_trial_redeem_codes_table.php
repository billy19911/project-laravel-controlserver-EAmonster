<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mt5_trial_redeem_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->unsignedSmallInteger('trial_days')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('redeemed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('redeemed_account_id', 32)->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'redeemed_at']);
            $table->index(['redeemed_by_user_id', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mt5_trial_redeem_codes');
    }
};
