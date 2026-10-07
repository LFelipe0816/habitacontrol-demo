<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->foreignId('block_id')->nullable()->after('community_id')->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->after('block_id')->constrained()->nullOnDelete();
            $table->string('apartment_number')->nullable()->after('code');
            $table->unsignedTinyInteger('floor')->nullable()->after('apartment_number');
            $table->string('parking')->nullable()->after('floor');
            $table->decimal('maintenance_fee', 10, 2)->default(0)->after('parking');
            $table->decimal('balance', 12, 2)->default(0)->after('maintenance_fee'); // deuda vigente, se recalcula con cobros y pagos
            $table->string('occupancy')->default('propietario')->after('balance'); // propietario | alquilado | vacante
            $table->date('move_in_date')->nullable()->after('occupancy');
            $table->json('emergency_contact')->nullable()->after('move_in_date');
            $table->json('vehicles')->nullable()->after('emergency_contact');
            $table->text('notes')->nullable()->after('vehicles');

            // El código (A01-301) solo debe ser único dentro de su residencial.
            $table->dropUnique(['code']);
            $table->unique(['community_id', 'code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('document_id')->nullable()->after('email'); // cédula
            $table->string('phone')->nullable()->after('document_id');
            $table->boolean('active')->default(true)->after('phone');
        });

        // Un usuario puede vincularse a varios apartamentos y un apartamento a varias personas.
        Schema::create('unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('relation'); // propietario | residente | inquilino
            $table->timestamps();
            $table->unique(['unit_id', 'user_id', 'relation']);
            $table->index('user_id');
        });

        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('monthly_rent', 12, 2);
            $table->decimal('deposit', 12, 2)->default(0);
            $table->string('authorized_by')->nullable();
            $table->timestamps();
            $table->index(['unit_id', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
        Schema::dropIfExists('unit_user');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['document_id', 'phone', 'active']));
        Schema::table('units', function (Blueprint $table) {
            // La FK de community_id necesita un índice propio antes de soltar el compuesto.
            $table->index('community_id');
            $table->dropUnique(['community_id', 'code']);
            $table->unique('code');
            $table->dropConstrainedForeignId('block_id');
            $table->dropConstrainedForeignId('building_id');
            $table->dropColumn(['apartment_number', 'floor', 'parking', 'maintenance_fee', 'balance', 'occupancy', 'move_in_date', 'emergency_contact', 'vehicles', 'notes']);
        });
    }
};
