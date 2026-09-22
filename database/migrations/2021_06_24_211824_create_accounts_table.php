<?php

use App\Enums\DiscountType;
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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->nullable(false);
            $table->integer('agent_id')->index()->default(0);
            $table->foreignId('expense_account')->default(0);
            $table->string('type', 30)->index()->nullable(false);
            $table->string('name', 100)->index()->nullable(false);
            $table->string('name_urdu')->nullable();
            $table->string('email', 100)->nullable(true);
            $table->float('discount')->default(0);
            $table->string('discount_type', 30)->default(DiscountType::FixedPerMeter->value);
            $table->json('commission_rate')->nullable(true);
            $table->string('phone', 100)->nullable(true);
            $table->json('address')->nullable(true);
            $table->integer('balance')->nullable();
            $table->date('balance_date')->nullable();
            $table->boolean('suspended')->default(false);
            $table->timestamp('suspended_at')->nullable();
            $table->float('limit', 10)->default(0);
            $table->boolean('credit')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
