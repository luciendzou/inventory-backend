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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id_product')->primary(); // UUID
            $table->string('nom', 255)->unique();
            $table->text('description')->nullable();
            $table->integer('quantite_stock')->default(0);
            $table->float('prix')->default(0);
            $table->integer('quantite_min_alerte')->default(5);
            $table->string('reference', 50)->nullable()->unique();
            $table->timestamps();

            // 🔗 Catégorie
            $table->foreignUuid('id_categorie')
                ->references('id_categorie')
                ->on('categories')
                ->onDelete('restrict');

            // 🔗 Marque
            $table->foreignUuid('id_marque')
                ->nullable()
                ->references('id_marque')
                ->on('brands')
                ->onDelete('set null');

            // 🔗 Fournisseur
            $table->foreignUuid('id_fournisseur')
                ->nullable()
                ->references('id_fournisseur')
                ->on('fournisseurs')
                ->onDelete('set null');

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
        Schema::dropIfExists('products');
    }
};
