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
        Schema::create('audit_reports', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('user_id')->nullable();
            $table->string('source', 20)->default('console');
            $table->unsignedInteger('issues_count')->default(0);
            $table->json('summary')->nullable();
            $table->json('issues')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_reports');
    }
};
