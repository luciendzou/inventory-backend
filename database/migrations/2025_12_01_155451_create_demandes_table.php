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
        Schema::create('demandes', function (Blueprint $table) {
            $table->uuid('id_demande')->primary();

            $table->uuid('id_users');
            $table->foreign('id_users')
                ->references('id_users')
                ->on('users')
                ->onDelete('restrict');

            // 🔥 ENTREPRISE (OBLIGATOIRE)
            $table->uuid('id_entreprise');
            $table->foreign('id_entreprise')
                ->references('id_entreprise')
                ->on('entreprises')
                ->onDelete('cascade');

            $table->timestamp('date_demande')->useCurrent();
            $table->string('statut', 50)->default('EN_ATTENTE');
            $table->text('motif')->nullable();
            $table->text('agence')->nullable();
            $table->text('notes_gestionnaire')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
