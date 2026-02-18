<?php

namespace App\Http\Controllers;

use App\Models\Ordonance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrdonanceController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Ordonance::query()
                ->where('id_entreprise', $request->user()->id_entreprise)
                ->orderByDesc('date')
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function show(Request $request, string $id)
    {
        $ordonance = Ordonance::query()
            ->where('id_ordonance', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        return response()->json($ordonance);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'compte_budgetaire' => 'required|string|max:150',
            'imputation_budgetaire' => 'required|string|max:150',
            'reference_op' => 'required|string|max:100|unique:ordonances,reference_op',
            'date' => 'required|date',
            'creancier' => 'required|string|max:255',
            'montant_brut' => 'required|numeric|min:0',
            'acompte' => 'nullable|numeric|min:0',
            'ir' => 'nullable|numeric|min:0',
            'tva' => 'nullable|numeric|min:0',
            'nap' => 'nullable|numeric|min:0',
            'nbre_pages_jointes' => 'required|integer|min:0',
            'observations' => 'nullable|string',
        ]);

        $acompte = (float) ($data['acompte'] ?? 0);
        $ir = (float) ($data['ir'] ?? 0);
        $tva = (float) ($data['tva'] ?? 0);
        $montantBrut = (float) $data['montant_brut'];
        $nap = array_key_exists('nap', $data)
            ? (float) $data['nap']
            : max($montantBrut - $acompte - $ir - $tva, 0);

        $ordonance = Ordonance::create([
            'id_ordonance' => (string) Str::uuid(),
            'id_entreprise' => $request->user()->id_entreprise,
            'id_users' => $request->user()->id_users,
            'compte_budgetaire' => $data['compte_budgetaire'],
            'imputation_budgetaire' => $data['imputation_budgetaire'],
            'reference_op' => $data['reference_op'],
            'date' => $data['date'],
            'creancier' => $data['creancier'],
            'montant_brut' => $montantBrut,
            'acompte' => $acompte,
            'ir' => $ir,
            'tva' => $tva,
            'nap' => $nap,
            'nbre_pages_jointes' => $data['nbre_pages_jointes'],
            'observations' => $data['observations'] ?? null,
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return response()->json($ordonance, 201);
    }

    public function approve(Request $request, string $id)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Acces refuse'], 403);
        }

        $ordonance = Ordonance::query()
            ->where('id_ordonance', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        if ($ordonance->status !== 'pending') {
            return response()->json([
                'message' => 'Seules les ordonances pending peuvent etre approuvees',
            ], 409);
        }

        $ordonance->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id_users,
            'approved_at' => now(),
        ]);

        return response()->json($ordonance);
    }

    private function isAdmin(): bool
    {
        return Auth::user()?->profil?->nom === 'Admin';
    }
}

