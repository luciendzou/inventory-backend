<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 * name="Categories",
 * description="API de gestion des catégories de l'entreprise"
 * )
 */
class CategorieController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/categories",
     * tags={"Categories"},
     * summary="Lister toutes les catégories",
     * description="Récupère la liste des catégories associées à l'entreprise de l'utilisateur connecté.",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Succès - Liste récupérée",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Categorie")
     * )
     * ),
     * @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request)
    {
        $categories = Categorie::where('id_entreprise', $request->user()->id_entreprise)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($categories);
    }

    /**
     * @OA\Post(
     * path="/api/categories",
     * tags={"Categories"},
     * summary="Créer une nouvelle catégorie",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * description="Données de la nouvelle catégorie",
     * @OA\JsonContent(
     * required={"name_cat", "type"},
     * @OA\Property(property="name_cat", type="string", example="Ordinateurs Portables", description="Nom de la catégorie"),
     * @OA\Property(property="type", type="string", example="Matériel", description="Type de la catégorie")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Succès - Catégorie créée",
     * @OA\JsonContent(ref="#/components/schemas/Categorie")
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_cat' => 'required|string|max:255',
            'type'     => 'required|string|max:255',
        ]);

        $user = $request->user();

        $categorie = Categorie::create([
            'id_categorie'  => (string) Str::uuid(),
            'id_entreprise' => $user->id_entreprise,
            'id_users'      => $user->id_users, // Ou $user->id selon votre table users
            'name_cat'      => $validated['name_cat'],
            'type'          => $validated['type'],
        ]);

        return response()->json($categorie, 201);
    }

    /**
     * @OA\Get(
     * path="/api/categories/{id}",
     * tags={"Categories"},
     * summary="Afficher les détails d'une catégorie",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="UUID de la catégorie",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=200,
     * description="Succès - Détails de la catégorie",
     * @OA\JsonContent(ref="#/components/schemas/Categorie")
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=404, description="Catégorie introuvable ou accès refusé")
     * )
     */
    public function show(Request $request, string $id)
    {
        $categorie = Categorie::where('id_categorie', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        return response()->json($categorie);
    }

    /**
     * @OA\Put(
     * path="/api/categories/{id}",
     * tags={"Categories"},
     * summary="Mettre à jour une catégorie",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="UUID de la catégorie",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Champs à mettre à jour",
     * @OA\JsonContent(
     * @OA\Property(property="name_cat", type="string", example="Imprimantes Laser"),
     * @OA\Property(property="type", type="string", example="Bureautique")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Succès - Catégorie mise à jour",
     * @OA\JsonContent(ref="#/components/schemas/Categorie")
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=404, description="Catégorie introuvable"),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(Request $request, string $id)
    {
        $categorie = Categorie::where('id_categorie', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        $validated = $request->validate([
            'name_cat' => 'sometimes|string|max:255',
            'type'     => 'sometimes|string|max:255',
        ]);

        $categorie->update($validated);

        return response()->json($categorie);
    }

    /**
     * @OA\Delete(
     * path="/api/categories/{id}",
     * tags={"Categories"},
     * summary="Supprimer une catégorie",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="UUID de la catégorie",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=204,
     * description="Succès - Catégorie supprimée"
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=404, description="Catégorie introuvable")
     * )
     */
    public function destroy(Request $request, string $id)
    {
        $categorie = Categorie::where('id_categorie', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        $categorie->delete();

        return response()->noContent();
    }
}