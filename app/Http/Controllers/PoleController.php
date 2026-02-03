<?php

namespace App\Http\Controllers;

use App\Models\Pole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/poles",
     *     summary="Lister tous les pôles",
     *     tags={"Poles"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des pôles",
     *         @OA\JsonContent(type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id_pole", type="string", format="uuid"),
     *                 @OA\Property(property="nom", type="string"),
     *                 @OA\Property(property="description", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return response()->json(Pole::all());
    }

    /**
     * @OA\Post(
     *     path="/api/poles",
     *     summary="Créer un pôle",
     *     tags={"Poles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom"},
     *             @OA\Property(property="nom", type="string", example="Pôle Logistique"),
     *             @OA\Property(property="description", type="string", example="Gère les opérations logistiques")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Pôle créé",
     *         @OA\JsonContent(
     *             @OA\Property(property="id_pole", type="string", format="uuid"),
     *             @OA\Property(property="nom", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $pole = Pole::create([
            'id_pole' => Str::uuid(),
            'nom' => $request->nom,
            'description' => $request->description,
        ]);

        return response()->json($pole, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/poles/{id}",
     *     summary="Afficher un pôle",
     *     tags={"Poles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du pôle",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pôle trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="id_pole", type="string"),
     *             @OA\Property(property="nom", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Pôle introuvable")
     * )
     */
    public function show(string $id)
    {
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json(['message' => 'Pôle introuvable'], 404);
        }

        return response()->json($pole);
    }

    /**
     * @OA\Put(
     *     path="/api/poles/{id}",
     *     summary="Modifier un pôle",
     *     tags={"Poles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du pôle",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="nom", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pôle mis à jour"
     *     ),
     *
     *     @OA\Response(response=404, description="Pôle introuvable")
     * )
     */
    public function update(Request $request, string $id)
    {
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json(['message' => 'Pôle introuvable'], 404);
        }

        $pole->update($request->only(['nom', 'description']));

        return response()->json(['message' => 'Pôle mis à jour', 'pole' => $pole]);
    }

    /**
     * @OA\Delete(
     *     path="/api/poles/{id}",
     *     summary="Supprimer un pôle",
     *     tags={"Poles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(response=200, description="Pôle supprimé"),
     *     @OA\Response(response=404, description="Pôle introuvable")
     * )
     */
    public function destroy(string $id)
    {
        $pole = Pole::find($id);

        if (!$pole) {
            return response()->json(['message' => 'Pôle introuvable'], 404);
        }

        $pole->delete();

        return response()->json(['message' => 'Pôle supprimé']);
    }
}
