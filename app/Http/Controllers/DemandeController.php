<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\LigneDemande;
use App\Models\SortieStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Demandes",
 *     description="Gestion des demandes de produits"
 * )
 */
class DemandeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/demandes",
     *     operationId="getAllDemandes",
     *     tags={"Demandes"},
     *     summary="Lister toutes les demandes de l’entreprise (Admin)",
     *     description="Accessible uniquement aux administrateurs. Retourne toutes les demandes de l’entreprise.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des demandes",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Demande")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function index(Request $request)
    {
        if ($request->user()->profil->nom !== 'Admin') {
            return response()->json(['message' => 'Accès interdit'], 403);
        }

        return Demande::with('lignes.product', 'user')
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->orderByDesc('date_demande')
            ->get();
    }

    /**
     * @OA\Post(
     *     path="/api/demandes",
     *     operationId="createDemande",
     *     tags={"Demandes"},
     *     summary="Créer une demande",
     *     description="Permet à un utilisateur de créer une demande de produits",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lignes"},
     *             @OA\Property(property="motif", type="string", example="Besoin pour le service IT"),
     *             @OA\Property(property="agence", type="string", example="Agence Yaoundé"),
     *             @OA\Property(
     *                 property="lignes",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id_product", type="string", format="uuid"),
     *                     @OA\Property(property="quantite_demandee", type="integer", example=5)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Demande créée avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Demande")
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'motif' => 'nullable|string',
            'lignes' => 'required|array|min:1',
            'lignes.*.id_product' => 'required|uuid|exists:products,id_product',
            'lignes.*.quantite_demandee' => 'required|integer|min:1',
            'agence' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        $demande = Demande::create([
            'id_demande'    => Str::uuid(),
            'id_users'      => $user->id_users,
            'id_entreprise' => $user->id_entreprise,
            'motif'         => $request->motif,
            'agence'         => $request->agence,
        ]);

        foreach ($request->lignes as $ligne) {
            LigneDemande::create([
                'id_ligne_demande' => Str::uuid(),
                'id_demande' => $demande->id_demande,
                'id_product' => $ligne['id_product'],
                'quantite_demandee' => $ligne['quantite_demandee'],
            ]);
        }

        return response()->json(
            $demande->load('lignes.product', 'user'),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/demandes/{id}",
     *     operationId="getDemandeById",
     *     tags={"Demandes"},
     *     summary="Afficher une demande",
     *     description="Retourne une demande. Accessible par le propriétaire ou un administrateur.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la demande",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la demande",
     *         @OA\JsonContent(ref="#/components/schemas/Demande")
     *     ),
     *     @OA\Response(response=403, description="Accès interdit"),
     *     @OA\Response(response=404, description="Demande introuvable")
     * )
     */
    public function show(Request $request, $id)
    {
        $demande = Demande::with('lignes.product', 'user')
            ->where('id_demande', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        if (
            $request->user()->id_users !== $demande->id_users &&
            $request->user()->profil->nom !== 'Admin'
        ) {
            abort(403);
        }

        return $demande;
    }

    /**
     * @OA\Get(
     *     path="/api/demandes/me",
     *     operationId="getMyDemandes",
     *     tags={"Demandes"},
     *     summary="Lister mes demandes",
     *     description="Retourne uniquement les demandes créées par l'utilisateur connecté",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des demandes de l'utilisateur",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Demande")
     *         )
     *     )
     * )
     */
    public function myDemandes(Request $request)
    {
        return Demande::with('lignes.product')
            ->where('id_users', $request->user()->id_users)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->orderByDesc('date_demande')
            ->get();
    }


    /**
     * @OA\Post(
     *     path="/api/demandes/{id}/validate",
     *     operationId="validateDemande",
     *     tags={"Demandes"},
     *     summary="Valider une demande",
     *     description="Permet à un administrateur de valider une demande en attente. 
     *     La validation génère automatiquement des sorties de stock en attente de confirmation.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Identifiant UUID de la demande à valider",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Demande validée et sorties créées",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Demande validée, sorties créées"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit (utilisateur non administrateur ou entreprise différente)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Accès interdit")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Demande non trouvée ou déjà traitée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not Found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Utilisateur non authentifié"
     *     )
     * )
     */
    public function validateDemande(Request $request, string $id)
    {
        $user = $request->user();

        // 🔐 Admin only
        if ($user->profil->nom !== 'Admin') {
            return response()->json(['message' => 'Accès interdit'], 403);
        }

        $demande = Demande::with('lignes')
            ->where('id_demande', $id)
            ->where('id_entreprise', $user->id_entreprise)
            ->where('statut', 'EN_ATTENTE')
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Demande introuvable ou déjà traitée'], 404);
        }

        // 🔄 Valider la demande
        $demande->update([
            'statut' => 'VALIDEE',
        ]);

        // 🔥 Génération des sorties de stock
        foreach ($demande->lignes as $ligne) {

            // Génération du numéro d'ordre
            $numOrdre = $this->generateNumeroOrdre();

            SortieStock::create([
                'id_sortie_stock'  => Str::uuid(),
                'num_ordre'        => $numOrdre,
                'id_product'       => $ligne->id_product,
                'id_demande'       => $demande->id_demande,
                'id_users'         => $demande->id_users,
                'quantite_sortie'  => $ligne->quantite_demandee,
                'statut_direction' => 'EN_ATTENTE',
            ]);
        }

        return response()->json([
            'message' => 'Demande validée, sorties créées',
        ]);
    }




    /**
     * @OA\Post(
     *     path="/api/demandes/{id}/reject",
     *     operationId="rejectDemande",
     *     tags={"Demandes"},
     *     summary="Refuser une demande",
     *     description="Permet à un administrateur de refuser une demande de produits encore en attente, appartenant à la même entreprise.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Identifiant UUID de la demande",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Demande refusée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Demande refusée"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit (utilisateur non administrateur ou entreprise différente)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Accès interdit")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Demande non trouvée ou déjà traitée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not Found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Utilisateur non authentifié"
     *     )
     * )
     */
    public function rejectDemande(Request $request, string $id)
    {
        $user = $request->user();

        if ($user->profil->nom !== 'Admin') {
            return response()->json(['message' => 'Accès interdit'], 403);
        }

        $demande = Demande::where('id_demande', $id)
            ->where('id_entreprise', $user->id_entreprise)
            ->where('statut', 'EN_ATTENTE')
            ->firstOrFail();

        if ($demande->statut !== 'EN_ATTENTE') {
            return response()->json([
                'message' => 'Une demande validée ou refusée ne peut plus être rejetée'
            ], 409);
        }

        $demande->update([
            'statut' => 'REFUSEE',
        ]);

        return response()->json([
            'message' => 'Demande refusée',
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/demandes/{id}/sorties",
     *     tags={"Demandes"},
     *     summary="Lister les sorties associées à une demande",
     *     description="Retourne toutes les sorties de stock générées pour une demande donnée. Accessible uniquement par l'admin ou le créateur de la demande.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la demande (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des sorties associées à la demande",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/SortieStock")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès interdit"),
     *     @OA\Response(response=404, description="Demande non trouvée")
     * )
     */
    public function sorties(Request $request, string $id)
    {
        $user = $request->user();

        // Récupérer la demande avec l'entreprise et les sorties
        $demande = Demande::with('sorties.product')
            ->where('id_demande', $id)
            ->where('id_entreprise', $user->id_entreprise)
            ->firstOrFail();

        // Vérification d'accès : admin ou créateur
        if ($user->profil->nom !== 'Admin' && $user->id_users !== $demande->id_users) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }

        return response()->json($demande->sorties);
    }


    private function generateNumeroOrdre()
    {
        $date = now()->format('Ymd');

        // Trouver la dernière sortie du jour
        $last = SortieStock::whereDate('created_at', now()->toDateString())
            ->orderBy('num_ordre', 'desc')
            ->first();

        if (!$last) {
            $sequence = "001";
        } else {
            // Extraire les 3 derniers digits
            $lastNumber = intval(substr($last->num_ordre, -3));
            $sequence = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        return "SO-$date-$sequence";
    }
}
