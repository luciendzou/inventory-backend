<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="EntreesStock",
 *     type="object",
 *     title="Entrée de stock",
 *     required={"id_entrees_stocks","id_product","quantite_entree","date_reception"},
 *
 *     @OA\Property(property="id_entrees_stocks", type="string", format="uuid"),
 *     @OA\Property(property="id_product", type="string", format="uuid"),
 *     @OA\Property(property="id_users", type="string", format="uuid"),
 *     @OA\Property(property="quantite_entree", type="integer", example=20),
 *     @OA\Property(property="num_ordre", type="string", example="ORD-001"),
 *     @OA\Property(property="fournisseur", type="string", nullable=true),
 *     @OA\Property(property="date_reception", type="string", format="date"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class EntreesStock extends Model
{
    use HasFactory;

    protected $table = 'entrees_stocks';
    protected $primaryKey = 'id_entrees_stocks';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_entrees_stocks',
        'id_product',
        'id_users',
        'num_ordre',
        'quantite_entree',
        'fournisseur',
        'date_reception',
    ];

    protected $casts = [
        'quantite_entree' => 'integer',
        'date_reception' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function productViaDemande()
    {
        // la migration lie id_demande à produits (id_product)
        return $this->belongsTo(Product::class, 'id_demande', 'id_product');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }
}
