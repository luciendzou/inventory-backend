<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->uuid('id_fournisseur')->primary();

            $table->uuid('id_entreprise');
            $table->uuid('id_users');

            $table->foreign('id_entreprise')->references('id_entreprise')->on('entreprises')->cascadeOnDelete();
            $table->foreign('id_users')->references('id_users')->on('users')->cascadeOnDelete();
            // Identité
            $table->string('nom', 255);
            $table->string('contact_nom')->nullable();

            // Coordonnées
            $table->string('email')->nullable();
            $table->string('telephone', 50)->nullable();
            $table->string('adresse')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('pays', 100)->nullable();

            // Métadonnées
            $table->boolean('actif')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
