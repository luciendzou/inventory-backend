<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Notification",
 *     type="object",
 *     @OA\Property(property="id_notification", type="string", format="uuid"),
 *     @OA\Property(property="id_users", type="string", format="uuid"),
 *     @OA\Property(property="id_entreprise", type="string", format="uuid"),
 *     @OA\Property(property="type", type="string", example="DEMANDE_VALIDEE"),
 *     @OA\Property(property="title", type="string", example="Demande validee"),
 *     @OA\Property(property="message", type="string", example="Votre demande a ete validee"),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="is_read", type="boolean", example=false),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';
    protected $primaryKey = 'id_notification';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_notification',
        'id_users',
        'id_entreprise',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'id_entreprise', 'id_entreprise');
    }
}

