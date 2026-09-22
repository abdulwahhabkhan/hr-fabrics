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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->nullableMorphs('resource');
            $table->string('reference_no')->index();
            $table->string('head', 30)->index();
            $table->boolean('has_file')->default(false);
            $table->json('source_info')->nullable();
            $table->string('detail', 400);
            $table->date('posted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
