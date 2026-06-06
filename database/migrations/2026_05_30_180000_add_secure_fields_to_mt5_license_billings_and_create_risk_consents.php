<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mt5_license_billings', function (Blueprint $table): void {
            if (!Schema::hasColumn('mt5_license_billings', 'mt5_server')) {
                $table->string('mt5_server', 120)->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('mt5_license_billings', 'mt5_password_encrypted')) {
                $table->text('mt5_password_encrypted')->nullable()->after('mt5_server');
            }
            if (!Schema::hasColumn('mt5_license_billings', 'tos_accepted_at')) {
                $table->timestamp('tos_accepted_at')->nullable()->after('processed_at');
            }
        });

        Schema::create('mt5_risk_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('account_id', 32);
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->unique(['user_id', 'account_id']);
            $table->index(['account_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mt5_risk_consents');

        Schema::table('mt5_license_billings', function (Blueprint $table): void {
            if (Schema::hasColumn('mt5_license_billings', 'tos_accepted_at')) {
                $table->dropColumn('tos_accepted_at');
            }
            if (Schema::hasColumn('mt5_license_billings', 'mt5_password_encrypted')) {
                $table->dropColumn('mt5_password_encrypted');
            }
            if (Schema::hasColumn('mt5_license_billings', 'mt5_server')) {
                $table->dropColumn('mt5_server');
            }
        });
    }
};
