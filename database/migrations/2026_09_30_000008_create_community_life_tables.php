<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('common_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Salón multiuso, Gazebo, Cancha…
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('common_area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->date('date');
            $table->time('starts_at');
            $table->time('ends_at')->nullable();
            $table->string('status')->default('pendiente'); // pendiente | confirmada | cancelada
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['common_area_id', 'date']); // detección de choques de horario
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category'); // Reglamentos, Actas, Finanzas…
            $table->string('visibility')->default('residentes'); // administracion | junta | residentes
            $table->string('path')->nullable();
            $table->string('file_name')->nullable();
            $table->timestamps();
        });

        // Solicitudes formales de residentes a la administración (consultas, permisos, reclamos).
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('status')->default('abierta')->index(); // abierta | en_proceso | resuelta | cerrada
            $table->text('response')->nullable();
            $table->timestamps();
        });

        // Mensajería interna; to_user_id nulo = difusión a toda la comunidad.
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['to_user_id', 'read_at']);
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('status')->default('activa'); // activa | cerrada
            $table->timestamps();
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->timestamps();
        });

        // Un voto por usuario y encuesta (índice único); el conteo se calcula, no se almacena.
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['poll_id', 'user_id']);
        });
    }

    public function down(): void
    {
        foreach (['poll_votes', 'poll_options', 'polls', 'messages', 'requests', 'documents', 'reservations', 'common_areas'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
