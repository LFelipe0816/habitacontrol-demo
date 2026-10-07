<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('residente')->after('password');
            $table->foreignId('community_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('community_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropConstrainedForeignId('community_id');
            $table->dropColumn('role');
        });
    }
};
