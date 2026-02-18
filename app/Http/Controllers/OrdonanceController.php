<?php

namespace App\Http\Controllers;

use App\Models\Ordonance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Ordonances",
 *     description="Gestion des ordonances de paiement"
 * )
 */
class OrdonanceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/ordonances",
     *     tags={"Ordonances"},
     *     summary="Lister les ordonances",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des ordonances",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Ordonance"))
     *     ),
     *     @OA\Response(response=401, description="Non authentifie")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/ordonances/{id}",
     *     tags={"Ordonances"},
     *     summary="Afficher une ordonance",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ordonance trouvee",
     *         @OA\JsonContent(ref="#/components/schemas/Ordonance")
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=404, description="Ordonance introuvable")
     * )
     */
    public function show(Request $request, string $id)
    {
        $ordonance = Ordonance::query()
            ->where('id_ordonance', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        return response()->json($ordonance);
    }

    /**
     * @OA\Post(
     *     path="/api/ordonances",
     *     tags={"Ordonances"},
     *     summary="Enregistrer une ordonance (status pending)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"compte_budgetaire","imputation_budgetaire","reference_op","date","creancier","montant_brut","nbre_pages_jointes"},
     *             @OA\Property(property="compte_budgetaire", type="string", example="601100"),
     *             @OA\Property(property="imputation_budgetaire", type="string", example="IB-2026-01"),
     *             @OA\Property(property="reference_op", type="string", example="OP-2026-0001"),
     *             @OA\Property(property="date", type="string", format="date", example="2026-02-18"),
     *             @OA\Property(property="creancier", type="string", example="Fournisseur XYZ"),
     *             @OA\Property(property="montant_brut", type="number", format="float", example=150000),
     *             @OA\Property(property="acompte", type="number", format="float", example=20000),
     *             @OA\Property(property="ir", type="number", format="float", example=5000),
     *             @OA\Property(property="tva", type="number", format="float", example=19250),
     *             @OA\Property(property="nap", type="number", format="float", example=105750),
     *             @OA\Property(property="nbre_pages_jointes", type="integer", example=3),
     *             @OA\Property(property="observations", type="string", example="Dossier complet")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ordonance enregistree",
     *         @OA\JsonContent(ref="#/components/schemas/Ordonance")
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/ordonances/{id}",
     *     tags={"Ordonances"},
     *     summary="Corriger une ordonance (pending uniquement)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="compte_budgetaire", type="string"),
     *             @OA\Property(property="imputation_budgetaire", type="string"),
     *             @OA\Property(property="reference_op", type="string"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="creancier", type="string"),
     *             @OA\Property(property="montant_brut", type="number", format="float"),
     *             @OA\Property(property="acompte", type="number", format="float"),
     *             @OA\Property(property="ir", type="number", format="float"),
     *             @OA\Property(property="tva", type="number", format="float"),
     *             @OA\Property(property="nap", type="number", format="float"),
     *             @OA\Property(property="nbre_pages_jointes", type="integer"),
     *             @OA\Property(property="observations", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ordonance corrigee",
     *         @OA\JsonContent(ref="#/components/schemas/Ordonance")
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=404, description="Ordonance introuvable"),
     *     @OA\Response(response=409, description="Impossible car deja approuvee"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(Request $request, string $id)
    {
        $ordonance = Ordonance::query()
            ->where('id_ordonance', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        if ($ordonance->status !== 'pending') {
            return response()->json([
                'message' => 'Impossible de modifier une ordonance approuvee',
            ], 409);
        }

        $data = $request->validate([
            'compte_budgetaire' => 'sometimes|required|string|max:150',
            'imputation_budgetaire' => 'sometimes|required|string|max:150',
            'reference_op' => 'sometimes|required|string|max:100|unique:ordonances,reference_op,' . $ordonance->id_ordonance . ',id_ordonance',
            'date' => 'sometimes|required|date',
            'creancier' => 'sometimes|required|string|max:255',
            'montant_brut' => 'sometimes|required|numeric|min:0',
            'acompte' => 'nullable|numeric|min:0',
            'ir' => 'nullable|numeric|min:0',
            'tva' => 'nullable|numeric|min:0',
            'nap' => 'nullable|numeric|min:0',
            'nbre_pages_jointes' => 'sometimes|required|integer|min:0',
            'observations' => 'nullable|string',
        ]);

        $ordonance->update($data);

        if (
            array_key_exists('montant_brut', $data) ||
            array_key_exists('acompte', $data) ||
            array_key_exists('ir', $data) ||
            array_key_exists('tva', $data)
        ) {
            if (!array_key_exists('nap', $data)) {
                $ordonance->nap = max(
                    (float) $ordonance->montant_brut
                    - (float) $ordonance->acompte
                    - (float) $ordonance->ir
                    - (float) $ordonance->tva,
                    0
                );
                $ordonance->save();
            }
        }

        return response()->json($ordonance->fresh());
    }

    /**
     * @OA\Delete(
     *     path="/api/ordonances/{id}",
     *     tags={"Ordonances"},
     *     summary="Supprimer une ordonance (pending uniquement)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=204, description="Ordonance supprimee"),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=404, description="Ordonance introuvable"),
     *     @OA\Response(response=409, description="Impossible car deja approuvee")
     * )
     */
    public function destroy(Request $request, string $id)
    {
        $ordonance = Ordonance::query()
            ->where('id_ordonance', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        if ($ordonance->status !== 'pending') {
            return response()->json([
                'message' => 'Impossible de supprimer une ordonance approuvee',
            ], 409);
        }

        $ordonance->delete();

        return response()->noContent();
    }

    /**
     * @OA\Post(
     *     path="/api/ordonances/{id}/approve",
     *     tags={"Ordonances"},
     *     summary="Valider une ordonance (status approved)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ordonance approuvee",
     *         @OA\JsonContent(ref="#/components/schemas/Ordonance")
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=403, description="Acces refuse"),
     *     @OA\Response(response=404, description="Ordonance introuvable"),
     *     @OA\Response(response=409, description="Conflit de statut")
     * )
     */
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
