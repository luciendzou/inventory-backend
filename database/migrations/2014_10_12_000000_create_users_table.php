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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid("id_users")->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone_number')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('agence')->nullable();
            $table->string('poste')->nullable(); // ajouté - poste ou service de travail
            $table->string('link_img')->nullable();
            $table->string('matricule')->nullable()->unique();
            $table->string('signature')->nullable();
            $table->uuid('profil_id')->default(5);
            $table->rememberToken();
            $table->timestamps();
            $table->foreign('profil_id')->references('id_profil')->on('profils');

            $table->uuid('id_entreprise');
            $table->foreign('id_entreprise')
                ->references('id_entreprise')
                ->on('entreprises')
                ->onDelete('cascade');

            $table->uuid('id_pole')->nullable();
            $table->foreign('id_pole')->references('id_pole')->on('poles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
