<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 50)->unique();
            $table->string('worker_name')->index();
            $table->string('worker_id', 50)
                ->collation('utf8mb4_unicode_ci')
                ->virtualAsJson('info->worker->id')
                ->nullable();
            $table->date('punch_date')->index();
            $table->time('punch_time');
            $table->string('method', 50)->nullable();
            $table->json('info')->nullable();
            $table->timestamps();
            $table->index(['worker_id', 'punch_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
