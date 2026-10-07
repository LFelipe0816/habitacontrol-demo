<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('concept');
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('status')->default('Por vencer')->index();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->timestamps();
        });

        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('notice_user', function (Blueprint $table) {
            $table->foreignId('notice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['notice_id', 'user_id']);
        });

        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained();
            $table->string('name');
            $table->string('document');
            $table->string('host');
            $table->string('plate')->nullable();
            $table->timestamp('entered_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
        Schema::dropIfExists('notice_user');
        Schema::dropIfExists('notices');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('charges');
    }
};
