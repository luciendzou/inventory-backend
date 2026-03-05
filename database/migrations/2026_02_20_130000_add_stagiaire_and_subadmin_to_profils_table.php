<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $profiles = [
            [
                'nom' => 'Stagiaire',
                'description' => 'Profil stagiaire avec acces restreint',
            ],
            [
                'nom' => 'subAdmin',
                'description' => 'Sous-administrateur avec privileges delegues',
            ],
        ];

        foreach ($profiles as $profile) {
            $exists = DB::table('profils')->where('nom', $profile['nom'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('profils')->insert([
                'id_profil' => (string) Str::uuid(),
                'nom' => $profile['nom'],
                'description' => $profile['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('profils')
            ->whereIn('nom', ['Stagiaire', 'subAdmin'])
            ->delete();
    }
};

