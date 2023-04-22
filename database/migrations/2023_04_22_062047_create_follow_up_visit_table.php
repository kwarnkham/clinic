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
        Schema::create('follow_up_visit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follow_up_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->date('due_on');
            $table->date('count_from');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_visit');
    }
};
