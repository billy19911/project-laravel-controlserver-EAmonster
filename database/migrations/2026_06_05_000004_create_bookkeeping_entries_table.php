<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookkeeping_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('account_id', 32);
            $table->date('entry_date');
            $table->double('daily_profit_usd')->default(0.0);
            $table->double('exchange_rate_idr')->default(16000.0);
            $table->double('profit_idr')->default(0.0);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'account_id', 'entry_date'], 'bookkeeping_user_account_date_unique');
            $table->index(['user_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookkeeping_entries');
    }
};
