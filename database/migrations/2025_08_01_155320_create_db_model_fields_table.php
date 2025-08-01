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
        Schema::create('db_model_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('db_model_id')->constrained('db_models')->onDelete('cascade');
            $table->foreignId('field_type_id')->constrained('db_model_field_types')->onDelete('cascade');
            $table->string('name'); // Nombre técnico del campo (para BD/código)
            $table->string('label'); // Etiqueta amigable para UX
            $table->string('default')->nullable();
            $table->boolean('nullable')->default(false);
            $table->boolean('unique')->default(false);
            $table->boolean('index')->default(false);
            $table->boolean('primary')->default(false);
            $table->boolean('auto_increment')->default(false);
            $table->boolean('foreign')->default(false);
            $table->string('foreign_table')->nullable();
            $table->string('foreign_key')->nullable();
            $table->timestamps();

            // Agregar índice único compuesto para evitar duplicados por nombre técnico
            $table->unique(['db_model_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('db_model_fields');
    }
};
