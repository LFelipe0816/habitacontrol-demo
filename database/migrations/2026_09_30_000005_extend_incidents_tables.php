<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->after('code')->constrained()->nullOnDelete();
            // Dónde ocurre: apartamento, edificio, manzana, calle o área común (ver location_type).
            $table->string('location_type')->default('common_area')->after('location'); // apartment | building | block | street | common_area
            $table->foreignId('block_id')->nullable()->after('location_type')->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->after('block_id')->constrained()->nullOnDelete();
            $table->foreignId('street_id')->nullable()->after('building_id')->constrained()->nullOnDelete();
            $table->string('scope')->nullable()->after('description'); // subcategoría: Iluminación exterior, Convivencia…
            $table->string('assignee_role')->nullable()->after('assignee_id'); // se asigna a un rol cuando aún no hay persona
            $table->timestamp('first_response_at')->nullable()->after('recurring'); // base del SLA de primera respuesta
            $table->timestamp('closed_at')->nullable()->after('first_response_at');
            $table->text('admin_notes')->nullable()->after('closed_at'); // notas internas, no visibles al residente
            $table->text('resolution')->nullable()->after('admin_notes');
            $table->string('recurrence_note')->nullable()->after('resolution');
            $table->index(['community_id', 'status']);
        });

        Schema::table('incident_attachments', function (Blueprint $table) {
            $table->string('mime')->nullable()->after('name');
            $table->unsignedBigInteger('size')->nullable()->after('mime');
        });
    }

    public function down(): void
    {
        Schema::table('incident_attachments', fn (Blueprint $table) => $table->dropColumn(['mime', 'size']));
        Schema::table('incidents', function (Blueprint $table) {
            // La FK usa el índice compuesto, así que se elimina primero la FK.
            $table->dropConstrainedForeignId('community_id');
            $table->dropIndex(['community_id', 'status']);
            $table->dropConstrainedForeignId('block_id');
            $table->dropConstrainedForeignId('building_id');
            $table->dropConstrainedForeignId('street_id');
            $table->dropColumn(['location_type', 'scope', 'assignee_role', 'first_response_at', 'closed_at', 'admin_notes', 'resolution', 'recurrence_note']);
        });
    }
};
