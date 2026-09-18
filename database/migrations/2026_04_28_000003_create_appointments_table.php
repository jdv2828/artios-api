<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('status', 20)->default('scheduled');
            $table->boolean('remind_1_day_before')->default(false);
            $table->boolean('remind_30_mins_before')->default(false);
            $table->boolean('remind_1_hour_before')->default(true);
            $table->timestamps();

            $table->index(['client_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
