<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->integer('min_confluence_score')->default(5)->nullable()->after('instant_reentry');
        });
    }

    public function down(): void
    {
        Schema::table('ea_configurations', function (Blueprint $table): void {
            $table->dropColumn('min_confluence_score');
        });
    }
};
