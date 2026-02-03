<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Fournisseur",
 *     type="object",
 *     title="Fournisseur",
 *     description="Fournisseur de produits",
 *
 *     @OA\Property(property="id_fournisseur", type="string", format="uuid"),
 *     @OA\Property(property="id_entreprise", type="string", format="uuid"),
 *     @OA\Property(property="id_users", type="string", format="uuid"),
 *
 *     @OA\Property(property="nom", type="string"),
 *     @OA\Property(property="contact_nom", type="string", nullable=true),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="telephone", type="string", nullable=true),
 *     @OA\Property(property="adresse", type="string", nullable=true),
 *     @OA\Property(property="ville", type="string", nullable=true),
 *     @OA\Property(property="pays", type="string", nullable=true),
 *     @OA\Property(property="actif", type="boolean"),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *
 *     @OA\Property(
 *         property="entreprise",
 *         ref="#/components/schemas/Entreprise"
 *     ),
 *
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/User"
 *     ),
 *
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */


class Fournisseur extends Model
{
    use HasFactory;

    protected $table = 'fournisseurs';
    protected $primaryKey = 'id_fournisseur';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_fournisseur',
        'id_entreprise',
        'id_users',
        'nom',
        'contact_nom',
        'email',
        'telephone',
        'adresse',
        'ville',
        'pays',
        'actif',
        'notes',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users');
    }
    // Relations
    public function entreesStocks()
    {
        return $this->hasMany(EntreesStock::class, 'id_fournisseur', 'id_fournisseur');
    }
}
