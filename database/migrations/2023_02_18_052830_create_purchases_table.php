<?php

use App\Enums\PurchaseStatus;
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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->integer('purchasable_id')->constrained();
            $table->string('purchasable_type')->constrained();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('stock');
            $table->double('price');
            $table->tinyInteger('status')->default(PurchaseStatus::NORMAL->value);
            $table->date('expired_on')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
