<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="LigneDemande",
 *     type="object",
 *     @OA\Property(property="id_ligne_demande", type="string", format="uuid"),
 *     @OA\Property(property="id_demande", type="string", format="uuid"),
 *     @OA\Property(property="id_product", type="string", format="uuid"),
 *     @OA\Property(property="quantite_demandee", type="integer"),
 *     @OA\Property(property="quantite_validee", type="integer")
 * )
 */
class LigneDemande extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_ligne_demande';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_ligne_demande',
        'id_demande',
        'id_product',
        'quantite_demandee',
        'quantite_validee'
    ];

    public function demande()
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
