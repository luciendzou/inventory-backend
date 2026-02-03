<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Categorie",
 *     type="object",
 *     title="Categorie",
 *     description="Catégorie de produits",
 *
 *     @OA\Property(property="id_categorie", type="string", format="uuid"),
 *     @OA\Property(property="id_entreprise", type="string", format="uuid"),
 *     @OA\Property(property="id_users", type="string", format="uuid"),
 *
 *     @OA\Property(property="name_cat", type="string"),
 *     @OA\Property(property="type", type="string"),
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


class Categorie extends Model
{
    use HasFactory;

    protected $table = 'categories';
    protected $primaryKey = 'id_categorie';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_categorie',
        'id_entreprise',
        'id_users',
        'name_cat',
        'type',
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
    public function products()
    {
        return $this->hasMany(Product::class, 'id_categorie', 'id_categorie');
    }
}
