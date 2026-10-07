<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Infraestructura compartida: planta eléctrica, pozos, purificación, tanques.
        Schema::create('service_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // Energía | Agua | ...
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('status')->default('operativo')->index(); // operativo | mantenimiento | alerta | fuera_servicio
            $table->unsignedTinyInteger('health')->default(100); // 0-100
            $table->decimal('availability', 5, 2)->nullable(); // % de disponibilidad
            $table->string('capacity')->nullable();
            $table->string('reading_label')->nullable(); // qué mide la lectura (Combustible, Caudal…)
            $table->unsignedTinyInteger('reading')->nullable(); // 0-100
            $table->string('responsible')->nullable();
            $table->string('provider')->nullable();
            $table->json('routine')->nullable(); // checklist de mantenimiento preventivo
            $table->text('risk')->nullable();
            $table->timestamps();
        });

        Schema::create('service_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scheduled_for');
            $table->date('performed_on')->nullable(); // nulo = aún pendiente
            $table->string('type')->default('preventivo'); // preventivo | correctivo
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['service_asset_id', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_maintenances');
        Schema::dropIfExists('service_assets');
    }
};
