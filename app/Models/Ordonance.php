<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Ordonance",
 *     type="object",
 *     title="Ordonance",
 *     @OA\Property(property="id_ordonance", type="string", format="uuid"),
 *     @OA\Property(property="id_entreprise", type="string", format="uuid"),
 *     @OA\Property(property="id_users", type="string", format="uuid"),
 *     @OA\Property(property="compte_budgetaire", type="string"),
 *     @OA\Property(property="imputation_budgetaire", type="string"),
 *     @OA\Property(property="reference_op", type="string"),
 *     @OA\Property(property="date", type="string", format="date"),
 *     @OA\Property(property="creancier", type="string"),
 *     @OA\Property(property="montant_brut", type="number", format="float"),
 *     @OA\Property(property="acompte", type="number", format="float"),
 *     @OA\Property(property="ir", type="number", format="float"),
 *     @OA\Property(property="tva", type="number", format="float"),
 *     @OA\Property(property="nap", type="number", format="float"),
 *     @OA\Property(property="nbre_pages_jointes", type="integer"),
 *     @OA\Property(property="observations", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="approved_by", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="approved_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Ordonance extends Model
{
    use HasFactory;

    protected $table = 'ordonances';
    protected $primaryKey = 'id_ordonance';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_ordonance',
        'id_entreprise',
        'id_users',
        'compte_budgetaire',
        'imputation_budgetaire',
        'reference_op',
        'date',
        'creancier',
        'montant_brut',
        'acompte',
        'ir',
        'tva',
        'nap',
        'nbre_pages_jointes',
        'observations',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'montant_brut' => 'float',
        'acompte' => 'float',
        'ir' => 'float',
        'tva' => 'float',
        'nap' => 'float',
        'nbre_pages_jointes' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise', 'id_entreprise');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id_users');
    }
}
