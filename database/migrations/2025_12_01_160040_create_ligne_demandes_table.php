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
        Schema::create('ligne_demandes', function (Blueprint $table) {
            $table->uuid('id_ligne_demande')->primary(); // UUID

            // Clés étrangères -- références explicites sur les PK correctes
            $table->foreignUuid('id_demande')->references('id_demande')->on('demandes')->onDelete('cascade');
            $table->foreignUuid('id_product')->references('id_product')->on('products')->onDelete('restrict');

            $table->integer('quantite_demandee');
            $table->integer('quantite_validee')->default(0);

            $table->unique(['id_demande', 'id_product']); // Empêcher les doublons
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ligne_demandes');
    }
};
