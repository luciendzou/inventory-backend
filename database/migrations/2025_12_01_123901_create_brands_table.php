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
        Schema::create('brands', function (Blueprint $table) {
            $table->uuid('id_marque')->primary();
            $table->string('nom', 255)->unique();
            $table->timestamps();

            $table->uuid('id_entreprise');
            $table->uuid('id_users');

            $table->foreign('id_entreprise')->references('id_entreprise')->on('entreprises')->cascadeOnDelete();
            $table->foreign('id_users')->references('id_users')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
