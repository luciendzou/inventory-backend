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
        $profiles = [
            ['nom' => 'Admin', 'description' => 'Administrateur de la plateforme avec acces complet'],
            ['nom' => 'Direction', 'description' => 'Acces aux rapports et statistiques globales'],
            ['nom' => 'Contrôle', 'description' => 'Controleur avec acces aux audits et verifications'],
            ['nom' => 'Agence', 'description' => "Responsable d'agence avec gestion locale"],
            ['nom' => 'Agent', 'description' => 'Agent standard avec acces limite'],
            ['nom' => 'Stagiaire', 'description' => 'Profil stagiaire avec acces restreint'],
            ['nom' => 'subAdmin', 'description' => 'Sous-administrateur avec privileges delegues'],
        ];

        foreach ($profiles as $profile) {
            $existing = Profil::where('nom', $profile['nom'])->first();

            if ($existing) {
                $existing->update([
                    'description' => $profile['description'],
                ]);
                continue;
            }

            Profil::create([
                'id_profil' => (string) Str::uuid(),
                'nom' => $profile['nom'],
                'description' => $profile['description'],
            ]);
        }
    }
}
