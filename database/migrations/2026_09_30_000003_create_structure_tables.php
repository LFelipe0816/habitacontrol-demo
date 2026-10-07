<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Manzanas: agrupan edificios y calles dentro del residencial.
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['community_id', 'code']);
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedTinyInteger('floors')->default(4);
            $table->unsignedTinyInteger('apartments_per_floor')->default(4);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['community_id', 'code']);
        });

        // Calles internas; block_id nulo = calle general del residencial.
        Schema::create('streets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('reference')->nullable();
            $table->unsignedSmallInteger('lighting_points')->default(0);
            $table->timestamps();
            $table->unique(['community_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streets');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('blocks');
    }
};
