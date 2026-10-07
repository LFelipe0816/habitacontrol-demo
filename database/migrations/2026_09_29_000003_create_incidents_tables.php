<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->foreignId('reporter_id')->constrained('users');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('type');
            $table->string('location');
            $table->text('description')->nullable();
            $table->string('priority')->default('Media');
            $table->string('status')->default('Recibida')->index();
            $table->boolean('recurring')->default(false);
            $table->timestamps();
        });

        Schema::create('incident_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('kind'); // res | int | acc
            $table->text('text');
            $table->timestamps();
        });

        Schema::create('incident_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->string('collection'); // evidence | solution
            $table->string('path');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_attachments');
        Schema::dropIfExists('incident_entries');
        Schema::dropIfExists('incidents');
    }
};
