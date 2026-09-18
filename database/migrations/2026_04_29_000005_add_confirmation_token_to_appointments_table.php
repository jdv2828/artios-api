<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('confirmation_token', 80)->nullable()->unique()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropUnique(['confirmation_token']);
            $table->dropColumn('confirmation_token');
        });
    }
};
