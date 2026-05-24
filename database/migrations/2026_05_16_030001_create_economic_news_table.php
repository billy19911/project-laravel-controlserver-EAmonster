<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('economic_news', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('event_at')->index();
            $table->string('currency', 8)->index();
            $table->string('impact', 16)->index();
            $table->string('title');
            $table->text('ai_analysis')->nullable();
            $table->string('ai_verdict', 32)->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['event_at', 'currency', 'title'], 'economic_news_unique_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('economic_news');
    }
};
