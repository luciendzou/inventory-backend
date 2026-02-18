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
        Schema::create('ordonances', function (Blueprint $table) {
            $table->uuid('id_ordonance')->primary();

            $table->uuid('id_entreprise');
            $table->uuid('id_users');

            $table->string('compte_budgetaire', 150);
            $table->string('imputation_budgetaire', 150);
            $table->string('reference_op', 100)->unique();
            $table->date('date');
            $table->string('creancier', 255);

            $table->decimal('montant_brut', 15, 2);
            $table->decimal('acompte', 15, 2)->default(0);
            $table->decimal('ir', 15, 2)->default(0);
            $table->decimal('tva', 15, 2)->default(0);
            $table->decimal('nap', 15, 2)->default(0);

            $table->unsignedInteger('nbre_pages_jointes')->default(0);
            $table->text('observations')->nullable();
            $table->string('status', 30)->default('pending')->index();

            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->foreign('id_entreprise')->references('id_entreprise')->on('entreprises')->cascadeOnDelete();
            $table->foreign('id_users')->references('id_users')->on('users')->restrictOnDelete();
            $table->foreign('approved_by')->references('id_users')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordonances');
    }
};

