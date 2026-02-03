<?php

namespace App\Http\Controllers;

use App\Models\Fournisseur;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 * name="Fournisseurs",
 * description="API de gestion des fournisseurs"
 * )
 */
class FournisseurController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/fournisseurs",
     * tags={"Fournisseurs"},
     * summary="Lister tous les fournisseurs",
     * description="Récupère la liste des fournisseurs de l'entreprise connectée.",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Succès - Liste récupérée",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Fournisseur")
     * )
     * ),
     * @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request)
    {
        $fournisseurs = Fournisseur::where('id_entreprise', $request->user()->id_entreprise)
            ->orderBy('nom', 'asc')
            ->get();

        return response()->json($fournisseurs);
    }

    /**
     * @OA\Post(
     * path="/api/fournisseurs",
     * tags={"Fournisseurs"},
     * summary="Créer un nouveau fournisseur",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * description="Données du fournisseur",
     * @OA\JsonContent(
     * required={"nom"},
     * @OA\Property(property="nom", type="string", example="Tech Supplier SARL"),
     * @OA\Property(property="contact_nom", type="string", example="Jean Dupont", nullable=true),
     * @OA\Property(property="email", type="string", format="email", example="contact@techsupplier.com", nullable=true),
     * @OA\Property(property="telephone", type="string", example="+237699000000", nullable=true),
     * @OA\Property(property="adresse", type="string", example="123 Rue de la République", nullable=true),
     * @OA\Property(property="ville", type="string", example="Douala", nullable=true),
     * @OA\Property(property="pays", type="string", example="Cameroun", nullable=true),
     * @OA\Property(property="actif", type="boolean", example=true, description="Statut du fournisseur"),
     * @OA\Property(property="notes", type="string", example="Fournisseur principal pour le matériel IT", nullable=true)
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Succès - Fournisseur créé",
     * @OA\JsonContent(ref="#/components/schemas/Fournisseur")
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'         => 'required|string|max:255',
            'contact_nom' => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'telephone'   => 'nullable|string|max:50',
            'adresse'     => 'nullable|string',
            'ville'       => 'nullable|string|max:100',
            'pays'        => 'nullable|string|max:100',
            'actif'       => 'boolean', // Validation boolean directe
            'notes'       => 'nullable|string',
        ]);

        $user = $request->user();

        // Fusion des données validées avec les données systèmes
        $fournisseurData = array_merge($validated, [
            'id_fournisseur' => (string) Str::uuid(),
            'id_entreprise'  => $user->id_entreprise,
            'id_users'       => $user->id_users,
            'actif'          => $request->input('actif', true), // Par défaut true si non spécifié
        ]);

        $fournisseur = Fournisseur::create($fournisseurData);

        return response()->json($fournisseur, 201);
    }

    /**
     * @OA\Get(
     * path="/api/fournisseurs/{id}",
     * tags={"Fournisseurs"},
     * summary="Afficher les détails d'un fournisseur",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="UUID du fournisseur",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=200,
     * description="Succès - Détails du fournisseur",
     * @OA\JsonContent(ref="#/components/schemas/Fournisseur")
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=404, description="Fournisseur introuvable")
     * )
     */
    public function show(Request $request, string $id)
    {
        $fournisseur = Fournisseur::where('id_fournisseur', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        return response()->json($fournisseur);
    }

    /**
     * @OA\Put(
     * path="/api/fournisseurs/{id}",
     * tags={"Fournisseurs"},
     * summary="Mettre à jour un fournisseur",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="UUID du fournisseur",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Champs à mettre à jour",
     * @OA\JsonContent(
     * @OA\Property(property="nom", type="string"),
     * @OA\Property(property="contact_nom", type="string"),
     * @OA\Property(property="email", type="string", format="email"),
     * @OA\Property(property="telephone", type="string"),
     * @OA\Property(property="adresse", type="string"),
     * @OA\Property(property="ville", type="string"),
     * @OA\Property(property="pays", type="string"),
     * @OA\Property(property="actif", type="boolean"),
     * @OA\Property(property="notes", type="string")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Succès - Fournisseur mis à jour",
     * @OA\JsonContent(ref="#/components/schemas/Fournisseur")
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=404, description="Fournisseur introuvable"),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(Request $request, string $id)
    {
        $fournisseur = Fournisseur::where('id_fournisseur', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        $validated = $request->validate([
            'nom'         => 'sometimes|string|max:255',
            'contact_nom' => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'telephone'   => 'nullable|string|max:50',
            'adresse'     => 'nullable|string',
            'ville'       => 'nullable|string|max:100',
            'pays'        => 'nullable|string|max:100',
            'actif'       => 'sometimes|boolean',
            'notes'       => 'nullable|string',
        ]);

        $fournisseur->update($validated);

        return response()->json($fournisseur);
    }

    /**
     * @OA\Delete(
     * path="/api/fournisseurs/{id}",
     * tags={"Fournisseurs"},
     * summary="Supprimer un fournisseur",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="UUID du fournisseur",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=204,
     * description="Succès - Fournisseur supprimé"
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=404, description="Fournisseur introuvable")
     * )
     */
    public function destroy(Request $request, string $id)
    {
        $fournisseur = Fournisseur::where('id_fournisseur', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        $fournisseur->delete();

        return response()->noContent();
    }
}