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
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users','id')->cascadeOnDelete();
            $table->string('city');
            $table->string('town');
            $table->string('street');
            $table->string('location')->nullable();
            $table->integer('space');
            $table->integer('rooms');
            $table->float('rating')->default(0);
            $table->integer('price_for_month');
            $table->string('description');
            $table->string('directions');
            $table->timestamps();
        });
    }

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
