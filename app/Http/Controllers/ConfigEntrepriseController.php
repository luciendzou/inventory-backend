<?php

namespace App\Http\Controllers;

use App\Models\ConfigEntreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="ConfigEntreprise",
 *     description="Gestion des configurations d'entreprise"
 * )
 */
class ConfigEntrepriseController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/config-entreprises",
     *     tags={"ConfigEntreprise"},
     *     summary="Liste des configurations d'entreprises",
     *     @OA\Response(
     *         response=200,
     *         description="Liste retournée avec succès",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/ConfigEntreprise")
     *         )
     *     )
     * )
     */
    public function index()
    {
        return response()->json(
            ConfigEntreprise::with('entreprise')->get()
        );
    }

    /**
     * @OA\Post(
     *     path="/api/config-entreprises",
     *     tags={"ConfigEntreprise"},
     *     summary="Créer une configuration d'entreprise",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id_entreprise","forfait","nbre_limit_personnel"},
     *             @OA\Property(property="id_entreprise", type="string", format="uuid"),
     *             @OA\Property(property="forfait", type="string"),
     *             @OA\Property(property="nbre_limit_personnel", type="string"),
     *             @OA\Property(property="actif", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Configuration créée",
     *         @OA\JsonContent(ref="#/components/schemas/ConfigEntreprise")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_entreprise' => 'required|uuid|exists:entreprises,id_entreprise|unique:config_entreprises,id_entreprise',
            'forfait' => 'required|string|max:255',
            'nbre_limit_personnel' => 'required|string|max:50',
            'actif' => 'nullable|boolean',
        ]);

        $data['id_config_entreprise'] = Str::uuid();
        $data['actif'] = $data['actif'] ?? false;

        $config = ConfigEntreprise::create($data);

        return response()->json($config, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/config-entreprises/{id}",
     *     tags={"ConfigEntreprise"},
     *     summary="Afficher une configuration",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuration trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/ConfigEntreprise")
     *     )
     * )
     */
    public function show(string $id)
    {
        return response()->json(
            ConfigEntreprise::with('entreprise')->findOrFail($id)
        );
    }

    /**
     * @OA\Put(
     *     path="/api/config-entreprises/{id}",
     *     tags={"ConfigEntreprise"},
     *     summary="Mettre à jour une configuration",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="forfait", type="string"),
     *             @OA\Property(property="nbre_limit_personnel", type="string"),
     *             @OA\Property(property="actif", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuration mise à jour",
     *         @OA\JsonContent(ref="#/components/schemas/ConfigEntreprise")
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $config = ConfigEntreprise::findOrFail($id);

        $data = $request->validate([
            'forfait' => 'sometimes|string|max:255',
            'nbre_limit_personnel' => 'sometimes|string|max:50',
            'actif' => 'sometimes|boolean',
        ]);

        $config->update($data);

        return response()->json($config);
    }

    /**
     * @OA\Delete(
     *     path="/api/config-entreprises/{id}",
     *     tags={"ConfigEntreprise"},
     *     summary="Supprimer une configuration",
     *     @OA\Response(
     *         response=204,
     *         description="Configuration supprimée"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        ConfigEntreprise::findOrFail($id)->delete();

        return response()->noContent();
    }


    /**
     * @OA\Get(
     *     path="/api/config-entreprises/by-entreprise/{idEntreprise}",
     *     tags={"ConfigEntreprise"},
     *     summary="Récupérer la configuration par entreprise",
     *     @OA\Parameter(
     *         name="idEntreprise",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuration trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/ConfigEntreprise")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Configuration non trouvée"
     *     )
     * )
     */
    public function showByEntreprise(string $idEntreprise)
    {
        $config = ConfigEntreprise::where('id_entreprise', $idEntreprise)->firstOrFail();

        return response()->json($config);
    }
}
