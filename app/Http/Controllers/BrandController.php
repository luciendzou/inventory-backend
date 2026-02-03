<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 * name="Brands",
 * description="API de gestion des marques"
 * )
 */
class BrandController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/brands",
     * tags={"Brands"},
     * summary="Lister toutes les marques",
     * description="Récupère la liste des marques associées à l'entreprise de l'utilisateur.",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Succès - Liste récupérée",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Brand")
     * )
     * ),
     * @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request)
    {
        $brands = Brand::where('id_entreprise', $request->user()->id_entreprise)
            ->orderBy('nom', 'asc') // Tri alphabétique par défaut
            ->get();

        return response()->json($brands);
    }

    /**
     * @OA\Post(
     * path="/api/brands",
     * tags={"Brands"},
     * summary="Créer une nouvelle marque",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * description="Données de la marque",
     * @OA\JsonContent(
     * required={"nom"},
     * @OA\Property(property="nom", type="string", example="Apple", description="Nom de la marque")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Succès - Marque créée",
     * @OA\JsonContent(ref="#/components/schemas/Brand")
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
        ]);

        $user = $request->user();

        $brand = Brand::create([
            'id_marque'     => (string) Str::uuid(),
            'id_entreprise' => $user->id_entreprise,
            'id_users'      => $user->id_users, // ou $user->id
            'nom'           => $validated['nom'],
        ]);

        return response()->json($brand, 201);
    }

    /**
     * @OA\Get(
     * path="/api/brands/{id}",
     * tags={"Brands"},
     * summary="Afficher les détails d'une marque",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="UUID de la marque",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=200,
     * description="Succès - Détails de la marque",
     * @OA\JsonContent(ref="#/components/schemas/Brand")
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=404, description="Marque introuvable")
     * )
     */
    public function show(Request $request, string $id)
    {
        $brand = Brand::where('id_marque', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        return response()->json($brand);
    }

    /**
     * @OA\Put(
     * path="/api/brands/{id}",
     * tags={"Brands"},
     * summary="Mettre à jour une marque",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="UUID de la marque",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Champs à modifier",
     * @OA\JsonContent(
     * @OA\Property(property="nom", type="string", example="Samsung")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Succès - Marque mise à jour",
     * @OA\JsonContent(ref="#/components/schemas/Brand")
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=404, description="Marque introuvable"),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(Request $request, string $id)
    {
        $brand = Brand::where('id_marque', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
        ]);

        $brand->update($validated);

        return response()->json($brand);
    }

    /**
     * @OA\Delete(
     * path="/api/brands/{id}",
     * tags={"Brands"},
     * summary="Supprimer une marque",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="UUID de la marque",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=204,
     * description="Succès - Marque supprimée"
     * ),
     * @OA\Response(response=401, description="Non authentifié"),
     * @OA\Response(response=404, description="Marque introuvable")
     * )
     */
    public function destroy(Request $request, string $id)
    {
        $brand = Brand::where('id_marque', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        $brand->delete();

        return response()->noContent();
    }
}