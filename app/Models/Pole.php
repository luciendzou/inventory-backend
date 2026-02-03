<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Pole",
 *     type="object",
 *     required={"id_pole","nom"},
 *
 *     @OA\Property(property="id_pole", type="string", format="uuid"),
 *     @OA\Property(property="nom", type="string", example="Pôle Informatique"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Support IT et réseaux"),
 *
 *     @OA\Property(
 *         property="users",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/User")
 *     )
 * )
 */
class Pole extends Model
{
    use HasFactory;
    protected $table = 'poles';

    protected $primaryKey = 'id_pole';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_pole',
        'nom',
        'description'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'id_pole', 'id_pole');
    }
}
