<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Demande",
 *     type="object",
 *     @OA\Property(property="id_demande", type="string", format="uuid"),
 *     @OA\Property(property="id_users", type="string", format="uuid"),
 *     @OA\Property(property="id_entreprise", type="string", format="uuid"),
 *     @OA\Property(property="date_demande", type="string", format="date-time"),
 *     @OA\Property(property="statut", type="string"),
 *     @OA\Property(property="motif", type="string"),
 *     @OA\Property(property="notes_gestionnaire", type="string")
 * )
 */
class Demande extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_demande';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_demande',
        'id_users',
        'id_entreprise',
        'statut',
        'motif',
        'agence',
        'notes_gestionnaire'
    ];

    public function lignes()
    {
        return $this->hasMany(LigneDemande::class, 'id_demande', 'id_demande');
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise', 'id_entreprise');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }

    public function sorties()
    {
        return $this->hasMany(SortieStock::class, 'id_demande', 'id_demande')
            ->with('product'); // pour récupérer les infos produit
    }
}
