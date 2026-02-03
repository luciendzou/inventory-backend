<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="ConfigEntreprise",
 *     type="object",
 *     title="Configuration Entreprise",
 *     @OA\Property(
 *         property="id_config_entreprise",
 *         type="string",
 *         format="uuid",
 *         description="Identifiant unique de la configuration"
 *     ),
 *     @OA\Property(
 *         property="id_entreprise",
 *         type="string",
 *         format="uuid",
 *         description="Entreprise liée"
 *     ),
 *     @OA\Property(
 *         property="forfait",
 *         type="string",
 *         description="Type de forfait"
 *     ),
 *     @OA\Property(
 *         property="nbre_limit_personnel",
 *         type="string",
 *         description="Nombre limite de personnel"
 *     ),
 *     @OA\Property(
 *         property="actif",
 *         type="string",
 *         description="Configuration active (0 ou 1)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     )
 * )
 */
class ConfigEntreprise extends Model
{
    use HasFactory;

    /** 🔹 Table */
    protected $table = 'config_entreprises';

    /** 🔹 Primary key */
    protected $primaryKey = 'id_config_entreprise';

    /** 🔹 UUID */
    public $incrementing = false;
    protected $keyType = 'string';

    /** 🔹 Champs autorisés */
    protected $fillable = [
        'id_config_entreprise',
        'id_entreprise',
        'forfait',
        'nbre_limit_personnel',
        'actif',
    ];

    /** 🔹 Casts */
    protected $casts = [
        'actif' => 'boolean',
    ];

    /** 🔗 Relations */

    public function entreprise()
    {
        return $this->belongsTo(
            Entreprise::class,
            'id_entreprise',
            'id_entreprise'
        );
    }
}
