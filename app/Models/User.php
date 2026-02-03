<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="Utilisateur du système",
 *
 *     @OA\Property(property="id_users", type="string", format="uuid"),
 *     @OA\Property(property="id_entreprise", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="phone_number", type="string"),
 *     @OA\Property(property="agence", type="string", nullable=true),
 *     @OA\Property(property="poste", type="string", nullable=true),
 *     @OA\Property(property="link_img", type="string", nullable=true),
 *     @OA\Property(property="matricule", type="string", nullable=true),
 *     @OA\Property(property="signature", type="string", nullable=true),
 *
 *     @OA\Property(
 *         property="entreprise",
 *         ref="#/components/schemas/Entreprise"
 *     ),
 *
 *     @OA\Property(
 *         property="profil",
 *         ref="#/components/schemas/Profil"
 *     ),
 *
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id_users';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_users',
        'id_entreprise',
        'id_pole',
        'name',
        'email',
        'phone_number',
        'password',
        'agence',
        'poste',
        'link_img',
        'profil_id',
        'matricule',
        'signature',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // 🔗 Relations
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise', 'id_entreprise');
    }

    public function profil()
    {
        return $this->belongsTo(Profil::class, 'profil_id', 'id_profil');
    }

    public function demandes()
    {
        return $this->hasMany(Demande::class, 'id_users', 'id_users');
    }

    public function entreesStocks()
    {
        return $this->hasMany(EntreesStock::class, 'id_users', 'id_users');
    }

    public function sortiesStocks()
    {
        return $this->hasMany(SortieStock::class, 'id_users', 'id_users');
    }

    public function pole()
    {
        return $this->belongsTo(Pole::class, 'id_pole', 'id_pole');
    }
}
