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
        Schema::create('sortie_stocks', function (Blueprint $table) {
            $table->uuid('id_sortie_stock')->primary(); // UUID

            $table->uuid('id_product');
            $table->foreign('id_product')
                ->references('id_product')
                ->on('products')
                ->onDelete('cascade');

            // 🔗 Demande
            $table->uuid('id_demande');
            $table->foreign('id_demande')
                ->references('id_demande')
                ->on('demandes')
                ->onDelete('cascade');

            // 🔗 Utilisateur
            $table->uuid('id_users');
            $table->foreign('id_users')
                ->references('id_users')
                ->on('users')
                ->onDelete('restrict');

            $table->integer('quantite_sortie');
            $table->string('num_ordre');
            $table->string('destination')->nullable();
            $table->string('motif')->nullable();

            $table->timestamp('date_sortie')->useCurrent();
            $table->string('statut_direction', 50)->default('EN_ATTENTE');

            $table->timestamps();

            // si vous vouliez que id_demande soit unique, ajoutez explicitement :
            // $table->unique('id_demande');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sortie_stocks');
    }
};
