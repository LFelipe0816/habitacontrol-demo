<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('units_count')->default(0)->after('meta'); // unidades contratadas (puede superar las cargadas)
            $table->unsignedTinyInteger('levels')->default(4)->after('units_count');
            $table->string('address')->nullable()->after('levels');
            $table->string('country')->default('República Dominicana')->after('address');
            $table->decimal('maintenance_fee', 10, 2)->default(0)->after('country');
            $table->decimal('implementation_cost', 12, 2)->default(0)->after('maintenance_fee');
            $table->string('status')->default('activo')->index()->after('implementation_cost'); // activo | prospecto | inactivo
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['units_count', 'levels', 'address', 'country', 'maintenance_fee', 'implementation_cost', 'status']);
        });
    }
};
