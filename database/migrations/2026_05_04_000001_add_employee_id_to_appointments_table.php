<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('service_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['employee_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['employee_id', 'scheduled_at']);
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
