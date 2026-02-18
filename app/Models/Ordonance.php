<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

