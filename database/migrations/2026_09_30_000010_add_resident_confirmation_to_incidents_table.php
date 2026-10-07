<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paso 4 de Inciden 360: quien reportó confirma (o rechaza) que el caso fue atendido.
        Schema::table('incidents', function (Blueprint $table) {
            $table->timestamp('resident_confirmed_at')->nullable()->after('closed_at');
            $table->text('resident_feedback')->nullable()->after('resident_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', fn (Blueprint $table) => $table->dropColumn(['resident_confirmed_at', 'resident_feedback']));
    }
};
