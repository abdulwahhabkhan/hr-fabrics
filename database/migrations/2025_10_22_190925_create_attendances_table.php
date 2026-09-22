<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('worker_id', 50)->index();
            $table->date('date')->index();
            $table->time('in')->nullable();
            $table->time('out')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->unique(['worker_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
