<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="SortieStock",
 *     type="object",
 *     title="Sortie de stock",
 *     description="Mouvement de sortie de stock d’un produit",
 *
 *     @OA\Property(
 *         property="id_sortie_stock",
 *         type="string",
 *         format="uuid",
 *         example="b3f6e8e4-9a7f-4f3c-bd12-1e8f98a56a11"
 *     ),
 *     @OA\Property(
 *         property="id_product",
 *         type="string",
 *         format="uuid",
 *         example="c1f8b1a4-3c21-4d7b-9f88-41b0b6e12345"
 *     ),
 *     @OA\Property(
 *         property="id_demande",
 *         type="string",
 *         format="uuid",
 *         example="d98c3c1e-29a5-4c1b-9f5e-3b6c5a1d9abc"
 *     ),
 *     @OA\Property(
 *         property="id_users",
 *         type="string",
 *         format="uuid",
 *         example="a12b4567-c890-4d3a-9123-456789abcdef"
 *     ),
 *     @OA\Property(
 *         property="quantite_sortie",
 *         type="integer",
 *         example=5
 *     ),
 *     @OA\Property(
 *         property="destination",
 *         type="string",
 *         example="Agence Nord"
 *     ),
 *     @OA\Property(
 *         property="motif",
 *         type="string",
 *         example="Dotation nouveau personnel"
 *     ),
 *     @OA\Property(
 *         property="num_ordre",
 *         type="string",
 *         example="ORD-001",
 *         description="Numéro d'ordre de la sortie"
 *     ),
 *     @OA\Property(
 *         property="statut_direction",
 *         type="string",
 *         example="EN_ATTENTE",
 *         description="Statut de validation par la direction"
 *     ),
 *     @OA\Property(
 *         property="date_sortie",
 *         type="string",
 *         format="date-time",
 *         example="2025-01-10T10:30:00Z"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-01-10T10:30:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-01-10T11:00:00Z"
 *     )
 * )
 */
class SortieStock extends Model
{
    use HasFactory;

    protected $table = 'sortie_stocks';
    protected $primaryKey = 'id_sortie_stock';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_sortie_stock',
        'id_product',
        'id_demande',
        'id_users',
        'num_ordre',
        'quantite_sortie',
        'destination',
        'motif',
        'date_sortie',
        'statut_direction',
    ];

    protected $casts = [
        'date_sortie' => 'datetime',
    ];

    /* =====================
        RELATIONS
    ====================== */

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function demande()
    {
        return $this->belongsTo(Demande::class, 'id_demande', 'id_demande');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }
}
