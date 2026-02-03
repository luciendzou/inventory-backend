<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 * name="Entreprises",
 * description="Gestion administrative des entreprises"
 * )
 */
class EntrepriseController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/entreprises",
     * tags={"Entreprises"},
     * summary="Lister les entreprises",
     * description="Récupère la liste des entreprises avec possibilité de filtrer par email.",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="email",
     * in="query",
     * required=false,
     * description="Filtrer par email de l'entreprise",
     * @OA\Schema(type="string", format="email")
     * ),
     * @OA\Response(
     * response=200,
     * description="Succès",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Entreprise"))
     * ),
     * @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request)
    {
        $query = Entreprise::query();

        if ($request->filled('email')) {
            $query->where('company_email', $request->email);
        }

        return response()->json($query->orderBy('company_name')->get());
    }

    /**
     * @OA\Post(
     * path="/api/entreprises",
     * tags={"Entreprises"},
     * summary="Créer une entreprise",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"company_name"},
     * @OA\Property(property="company_name", type="string", example="Ma Super Entreprise"),
     * @OA\Property(property="tax_number", type="string", example="NIU123456"),
     * @OA\Property(property="company_email", type="string", format="email", example="contact@entreprise.com"),
     * @OA\Property(property="address", type="string", example="Douala, Cameroun")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Entreprise créée",
     * @OA\JsonContent(ref="#/components/schemas/Entreprise")
     * ),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'  => 'required|string|max:255',
            'tax_number'    => 'nullable|string|unique:entreprises,tax_number',
            'address'       => 'nullable|string',
            'company_email' => 'nullable|email|unique:entreprises,company_email',
            'logo'          => 'nullable|string',
            'manager_name'  => 'nullable|string|max:255',
            'manager_email' => 'nullable|email',
            'manager_phone' => 'nullable|string|max:50',
            'manager_nui'   => 'nullable|string|unique:entreprises,manager_nui',
        ]);

        $validated['id_entreprise'] = (string) Str::uuid();

        $entreprise = Entreprise::create($validated);

        return response()->json($entreprise, 201);
    }

    /**
     * @OA\Get(
     * path="/api/entreprises/{id}",
     * tags={"Entreprises"},
     * summary="Détails d'une entreprise",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="UUID de l'entreprise",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=200,
     * description="Succès",
     * @OA\JsonContent(ref="#/components/schemas/Entreprise")
     * ),
     * @OA\Response(response=404, description="Entreprise non trouvée")
     * )
     */
    public function show(string $id)
    {
        $entreprise = Entreprise::where('id_entreprise', $id)->firstOrFail();
        return response()->json($entreprise);
    }

    /**
     * @OA\Put(
     * path="/api/entreprises/{id}",
     * tags={"Entreprises"},
     * summary="Mettre à jour une entreprise",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(ref="#/components/schemas/Entreprise")
     * ),
     * @OA\Response(response=200, description="Mise à jour réussie"),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(Request $request, string $id)
    {
        $entreprise = Entreprise::where('id_entreprise', $id)->firstOrFail();

        $validated = $request->validate([
            'company_name'  => 'sometimes|required|string|max:255',
            'tax_number'    => "nullable|string|unique:entreprises,tax_number,{$id},id_entreprise",
            'address'       => 'nullable|string',
            'company_email' => "nullable|email|unique:entreprises,company_email,{$id},id_entreprise",
            'logo'          => 'nullable|string',
            'manager_name'  => 'nullable|string|max:255',
            'manager_email' => 'nullable|email',
            'manager_phone' => 'nullable|string|max:50',
            'manager_nui'   => "nullable|string|unique:entreprises,manager_nui,{$id},id_entreprise",
        ]);

        $entreprise->update($validated);

        return response()->json($entreprise);
    }

    /**
     * @OA\Delete(
     * path="/api/entreprises/{id}",
     * tags={"Entreprises"},
     * summary="Supprimer une entreprise",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(response=204, description="Supprimé avec succès"),
     * @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function destroy(string $id)
    {
        $entreprise = Entreprise::where('id_entreprise', $id)->firstOrFail();
        $entreprise->delete();

        return response()->noContent();
    }
}