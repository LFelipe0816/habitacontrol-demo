<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Administradora que opera varios residenciales (el "cliente" de HabitaControl).
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tax_id')->nullable(); // RNC
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // Paquetes comerciales; alimentan la cotización por unidad.
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // incidents | integral | enterprise
            $table->string('name');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('implementation_cost', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
        Schema::dropIfExists('companies');
    }
};
