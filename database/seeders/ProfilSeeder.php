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
            'description' => 'Administrateur de la plateforme avec accès complet',
        ]);

        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'Direction',
            'description' => 'Accès aux rapports et statistiques globales',
        ]);

        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'Contrôle',
            'description' => 'Contrôleur avec accès aux audits et vérifications',
        ]);

        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'Agence',
            'description' => 'Responsable d\'agence avec gestion locale',
        ]);

        Profil::create([
            'id_profil' => Str::uuid(),
            'nom' => 'Agent',
            'description' => 'Agent standard avec accès limité',
        ]);
    }
}
