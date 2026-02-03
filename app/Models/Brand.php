<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Brand",
 *     type="object",
 *     title="Brand",
 *     description="Marque de produit",
 *
 *     @OA\Property(property="id_marque", type="string", format="uuid"),
 *     @OA\Property(property="id_entreprise", type="string", format="uuid"),
 *     @OA\Property(property="id_users", type="string", format="uuid"),
 *
 *     @OA\Property(property="nom", type="string"),
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


class Brand extends Model
{
    use HasFactory;
    protected $table = 'brands';
    protected $primaryKey = 'id_marque';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_marque',
        'id_entreprise',
        'id_users',
        'nom',
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
        return $this->hasMany(Product::class, 'id_marque', 'id_marque');
    }
}
