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
        Schema::create('product_visit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->double('sale_price');
            $table->double('last_purchase_price')->nullable();
            $table->unsignedInteger('stock');
            $table->unsignedInteger('quantity');
            $table->double('discount')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_visit');
    }
};
