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
        Schema::create('db_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('table')->unique();
            $table->json('fillable')->nullable();
            $table->json('guarded')->nullable();
            $table->json('with')->nullable();
            $table->json('hidden')->nullable();
            $table->json('appends')->nullable();
            $table->json('casts')->nullable();
            $table->json('relations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('db_models');
    }
};
