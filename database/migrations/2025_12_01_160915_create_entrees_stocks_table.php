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
        Schema::create('entrees_stocks', function (Blueprint $table) {
            $table->uuid('id_entrees_stocks')->primary(); // UUID
            
            // Clés étrangères (références explicites sur les PK correctes)
            $table->foreignUuid('id_product')->references('id_product')->on('products')->onDelete('restrict');
            $table->foreignUuid('id_users')->references('id_users')->on('users')->onDelete('restrict'); // Qui a enregistré l'entrée
            
            $table->integer('quantite_entree');
            $table->string('num_ordre');
            $table->string('fournisseur')->nullable();
            $table->date('date_reception');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrees_stocks');
    }
};
