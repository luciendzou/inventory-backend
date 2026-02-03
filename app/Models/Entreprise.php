<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Entreprise",
 *     type="object",
 *     title="Entreprise",
 *     description="Informations sur une entreprise",
 *
 *     @OA\Property(
 *         property="id_entreprise",
 *         type="string",
 *         format="uuid",
 *         description="Identifiant unique de l'entreprise"
 *     ),
 *
 *     @OA\Property(
 *         property="company_name",
 *         type="string",
 *         maxLength=255,
 *         description="Nom de l'entreprise"
 *     ),
 *
 *     @OA\Property(
 *         property="tax_number",
 *         type="string",
 *         nullable=true,
 *         description="Numéro fiscal"
 *     ),
 *
 *     @OA\Property(
 *         property="address",
 *         type="string",
 *         nullable=true,
 *         description="Adresse de l'entreprise"
 *     ),
 *
 *     @OA\Property(
 *         property="company_email",
 *         type="string",
 *         format="email",
 *         nullable=true,
 *         description="Email de l'entreprise"
 *     ),
 *
 *     @OA\Property(
 *         property="logo",
 *         type="string",
 *         nullable=true,
 *         description="Logo (URL ou chemin)"
 *     ),
 *
 *     @OA\Property(
 *         property="manager_name",
 *         type="string",
 *         nullable=true,
 *         description="Nom du responsable"
 *     ),
 *
 *     @OA\Property(
 *         property="manager_email",
 *         type="string",
 *         format="email",
 *         nullable=true,
 *         description="Email du responsable"
 *     ),
 *
 *     @OA\Property(
 *         property="manager_phone",
 *         type="string",
 *         nullable=true,
 *         description="Téléphone du responsable"
 *     ),
 *
 *     @OA\Property(
 *         property="manager_nui",
 *         type="string",
 *         nullable=true,
 *         description="NUI du responsable"
 *     ),
 *
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création"
 *     ),
 *
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de mise à jour"
 *     )
 * )
 */

class Entreprise extends Model
{
    use HasFactory;

    protected $table = 'entreprises';

    protected $primaryKey = 'id_entreprise';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_entreprise',
        'company_name',
        'tax_number',
        'address',
        'company_email',
        'logo',
        'manager_name',
        'manager_email',
        'manager_phone',
        'manager_nui',
    ];

    /**
     * Casts (optionnels mais propres)
     */
    protected $casts = [
        'id_entreprise' => 'string',
    ];
}
