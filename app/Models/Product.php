<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Produit",
 *     type="object",
 *     title="Produit",
 *     description="Produit géré par une entreprise",
 *
 *     @OA\Property(property="id_product", type="string", format="uuid"),
 *     @OA\Property(property="id_entreprise", type="string", format="uuid"),
 *     @OA\Property(property="id_users", type="string", format="uuid"),
 *
 *     @OA\Property(property="id_categorie", type="string", format="uuid"),
 *     @OA\Property(property="id_marque", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="id_fournisseur", type="string", format="uuid", nullable=true),
 *
 *     @OA\Property(property="nom", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="reference", type="string", nullable=true),
 *     @OA\Property(property="prix", type="float"),
 *     @OA\Property(property="quantite_stock", type="integer"),
 *     @OA\Property(property="quantite_min_alerte", type="integer"),
 *
 *     @OA\Property(
 *         property="categorie",
 *         ref="#/components/schemas/Categorie"
 *     ),
 *
 *     @OA\Property(
 *         property="marque",
 *         ref="#/components/schemas/Brand"
 *     ),
 *
 *     @OA\Property(
 *         property="fournisseur",
 *         ref="#/components/schemas/Fournisseur"
 *     ),
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


class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $primaryKey = 'id_product';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_product',
        'id_entreprise',
        'id_users',
        'id_categorie',
        'id_marque',
        'id_fournisseur',
        'nom',
        'description',
        'reference',
        'quantite_stock',
        'prix',
        'quantite_min_alerte',
        'is_direction',
        'agence',
    ];

    protected $casts = [
        'quantite_stock' => 'integer',
        'quantite_min_alerte' => 'integer',
        'is_direction' => 'boolean',
        'prix' => 'float',
    ];
    

    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'id_categorie', 'id_categorie');
    }

    public function marque()
    {
        return $this->belongsTo(Brand::class, 'id_marque', 'id_marque');
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'id_fournisseur', 'id_fournisseur');
    }

    // Scopes / helpers
    public function scopeDirection($query)
    {
        return $query->where('is_direction', true);
    }

    public function scopeForAgence($query, string $agence)
    {
        return $query->where('is_direction', false)->where('agence', $agence);
    }

    public function isDirection(): bool
    {
        return (bool) $this->is_direction;
    }

    public function belongsToAgence(): ?string
    {
        return $this->is_direction ? null : $this->agence;
    }

    // relations to demandes/entrees/sorties/lignes si déjà définies
    public function lignesDemande()
    {
        return $this->hasMany(LigneDemande::class, 'id_product', 'id_product');
    }

    public function entreesStocks()
    {
        return $this->hasMany(EntreesStock::class, 'id_demande', 'id_product');
    }

    public function sortiesStocks()
    {
        return $this->hasMany(SortieStock::class, 'id_demande', 'id_product');
    }
}
