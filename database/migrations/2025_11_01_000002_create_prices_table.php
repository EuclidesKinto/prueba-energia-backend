<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->unique();

            for ($h = 1; $h <= 25; $h++) {
                $table->double("h{$h}")->nullable();
            }

            $table->timestamps();

            $table->index('date', 'idx_prices_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
