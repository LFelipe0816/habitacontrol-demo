<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('charge_id')->nullable()->change(); // un abono puede no ligarse a un cobro concreto
            $table->foreignId('unit_id')->nullable()->after('charge_id')->constrained()->nullOnDelete(); // abono a la unidad sin cobro específico
            $table->string('reference')->nullable()->after('method');
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->after('community_id')->constrained('users')->nullOnDelete();
            $table->string('audience')->default('todos'); // todos | propietarios | residentes | junta
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->string('reason')->nullable()->after('plate');
            $table->string('status')->default('dentro')->index()->after('reason'); // esperado | dentro | salio
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn(['reason', 'status']);
        });
        Schema::table('notices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('community_id');
            $table->dropConstrainedForeignId('author_id');
            $table->dropColumn('audience');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn('reference');
            $table->foreignId('charge_id')->nullable(false)->change();
        });
    }
};
