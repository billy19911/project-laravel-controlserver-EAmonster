<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ea_closed_trades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ea_configuration_id')->constrained('ea_configurations')->cascadeOnDelete();
            $table->string('account_id', 32)->index();
            $table->string('ticket', 96);
            $table->string('symbol', 24)->nullable();
            $table->string('type', 16)->nullable();
            $table->decimal('lot', 16, 4)->default(0);
            $table->decimal('open_price', 18, 6)->default(0);
            $table->decimal('close_price', 18, 6)->default(0);
            $table->decimal('profit', 18, 2)->default(0);
            $table->decimal('swap', 18, 2)->default(0);
            $table->decimal('commission', 18, 2)->default(0);
            $table->string('open_time_text', 64)->nullable();
            $table->string('close_time_text', 64)->nullable();
            $table->timestamp('open_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['account_id', 'ticket']);
            $table->index(['ea_configuration_id', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ea_closed_trades');
    }
};
