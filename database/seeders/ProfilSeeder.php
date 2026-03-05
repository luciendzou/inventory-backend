<?php

namespace Database\Seeders;

use App\Models\Profil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProfilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'Admin',
            'description' => 'Administrateur de la plateforme avec acces complet',
        ]);

        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'Direction',
            'description' => 'Acces aux rapports et statistiques globales',
        ]);

        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'Controle',
            'description' => 'Controleur avec acces aux audits et verifications',
        ]);

        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'Agence',
            'description' => "Responsable d'agence avec gestion locale",
        ]);

        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'Agent',
            'description' => 'Agent standard avec acces limite',
        ]);

        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'Stagiaire',
            'description' => 'Profil stagiaire avec acces restreint',
        ]);

        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'subAdmin',
            'description' => 'Sous-administrateur avec privileges delegues',
        ]);
    }
}

