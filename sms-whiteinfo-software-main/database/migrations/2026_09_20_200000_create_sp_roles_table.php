<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sp_roles', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 64)->index(); // 'superman' for internal company, or string SP owner ID
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->json('permissions');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['scope', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_roles');
    }
};
