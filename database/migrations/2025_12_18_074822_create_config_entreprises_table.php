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
        Schema::create('config_entreprises', function (Blueprint $table) {
            $table->uuid('id_config_entreprise')->primary();
            $table->uuid('id_entreprise');
            $table->string('forfait');
            $table->string('nbre_limit_personnel');
            $table->string('actif')->default('0');
            $table->timestamps();
            
            $table->foreign('id_entreprise')
                ->references('id_entreprise')
                ->on('entreprises')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_entreprises');
    }
};
