<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Profil",
 *     type="object",
 *     title="Profil",
 *     @OA\Property(property="id_profil", type="string", format="uuid", description="Profile's unique identifier"),
 *     @OA\Property(property="nom", type="string", description="Profile's name"),
 *     @OA\Property(property="description", type="string", description="Profile's description"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
 * )
 */

class Profil extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_profil';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_profil',
        'nom',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'profil_id', 'id_profil');
    }
}
