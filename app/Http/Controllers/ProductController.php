<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\Product;
use App\Models\EntreesStock;
use App\Models\SortieStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 * name="Products",
 * description="Gestion des produits et mouvements de stock"
 * )
 */
class ProductController extends Controller
{
    /* =======================
        HELPERS
    ======================== */

    private function isAdmin(): bool
    {
        return Auth::user()?->profil?->nom === 'Admin';
    }

    private function isDirectionOrControle(): bool
    {
        return in_array(Auth::user()?->profil?->nom, ['Direction', 'Contrôle']);
    }

    /* =======================
        CRUD PRODUITS
    ======================== */

    /**
     * @OA\Get(
     * path="/api/products",
     * tags={"Products"},
     * summary="Lister les produits",
     * description="Récupère le stock actuel de l'entreprise.",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Liste des produits",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Product"))
     * ),
     * @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        return Product::where('id_entreprise', $request->user()->id_entreprise)
            ->orderBy('nom')
            ->get();
    }

    /**
     * @OA\Post(
     * path="/api/products",
     * tags={"Products"},
     * summary="Créer un produit (Admin)",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nom", "quantite_stock", "quantite_min_alerte"},
     * @OA\Property(property="nom", type="string", example="Ordinateur Portable"),
     * @OA\Property(property="description", type="string", example="Dell Latitude 5420"),
     * @OA\Property(property="quantite_stock", type="integer", example=10),
     * @OA\Property(property="prix", type="float", example=10.5),
     * @OA\Property(property="quantite_min_alerte", type="integer", example=2),
     * @OA\Property(property="reference", type="string", example="REF-001"),
     * @OA\Property(property="agence", type="string", example="Siège")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Produit créé",
     * @OA\JsonContent(ref="#/components/schemas/Product")
     * ),
     * @OA\Response(response=403, description="Accès refusé"),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }


        $data = $request->validate([
            'nom'                 => 'required|string|max:255',
            'description'         => 'nullable|string',
            'quantite_stock'      => 'required|integer|min:0',
            'prix'                => 'nullable|numeric',
            'quantite_min_alerte' => 'required|integer|min:0',
            'reference'           => 'nullable|string|max:255',
            'agence'              => 'nullable|string|max:255',
            'id_fournisseur'              => 'nullable|string|max:255',
            'id_categorie'              => 'nullable|string|max:255',
            'id_marque'              => 'nullable|string|max:255',
        ]);

        $data['id_product']    = (string) Str::uuid();
        $data['id_entreprise'] = $request->user()->id_entreprise;
        $data['id_users']      = $request->user()->id_users;

        return response()->json(Product::create($data), 201);
    }

    /**
     * @OA\Get(
     * path="/api/products/{id}",
     * tags={"Products"},
     * summary="Afficher un produit",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     * response=200,
     * description="Détails du produit",
     * @OA\JsonContent(ref="#/components/schemas/Product")
     * ),
     * @OA\Response(response=404, description="Produit non trouvé")
     * )
     */
    public function show(Request $request, string $id)
    {
        return Product::where('id_product', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();
    }

    /**
     * @OA\Put(
     * path="/api/products/{id}",
     * tags={"Products"},
     * summary="Mettre à jour un produit (Admin)",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nom", type="string"),
     * @OA\Property(property="description", type="string"),
     * @OA\Property(property="quantite_min_alerte", type="integer"),
     * @OA\Property(property="prix", type="float"),
     * @OA\Property(property="reference", type="string"),
     * @OA\Property(property="agence", type="string")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Produit mis à jour",
     * @OA\JsonContent(ref="#/components/schemas/Product")
     * ),
     * @OA\Response(response=403, description="Accès refusé"),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(Request $request, string $id)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        /* if (SortieStock::where('id_product', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->exists()) {
            return response()->json(['message' => 'Le Produit ne plus être modifié car il a des sorties en stock'], 500);
        } */

        $product = Product::where('id_product', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        $data = $request->validate([
            'nom'                 => 'sometimes|required|string|max:255',
            'description'         => 'nullable|string',
            'quantite_min_alerte' => 'sometimes|required|integer|min:0',
            'prix'                => 'nullable|float',
            'reference'           => 'nullable|string|max:255',
            'agence'              => 'nullable|string|max:255',
            'id_fournisseur'              => 'nullable|string|max:255',
            'id_categorie'              => 'nullable|string|max:255',
            'id_marque'              => 'nullable|string|max:255',
            // On évite de modifier 'quantite_stock' ici, on passe par les Entrées/Sorties
        ]);

        $product->update($data);

        return response()->json($product);
    }

    /**
     * @OA\Delete(
     * path="/api/products/{id}",
     * tags={"Products"},
     * summary="Supprimer un produit (Admin)",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(response=204, description="Produit supprimé"),
     * @OA\Response(response=403, description="Accès refusé"),
     * @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function destroy(Request $request, string $id)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        Product::where('id_product', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail()
            ->delete();

        return response()->noContent();
    }

    /* =======================
        ENTRÉE DE STOCK
    ======================== */

    /**
     * @OA\Post(
     * path="/api/products/{id}/entries",
     * tags={"Products"},
     * summary="Ajouter du stock (Admin)",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID du produit",
     * @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"quantite_entree", "date_reception"},
     * @OA\Property(property="quantite_entree", type="integer", example=50),
     * @OA\Property(property="num_ordre", type="string", example="ORD-001"),
     * @OA\Property(property="fournisseur", type="string", example="Fournisseur Global"),
     * @OA\Property(property="date_reception", type="string", format="date", example="2024-01-01")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Stock ajouté",
     * @OA\JsonContent(ref="#/components/schemas/EntreesStock")
     * ),
     * @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function storeEntry(Request $request, string $id)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $product = Product::where('id_product', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        $data = $request->validate([
            'quantite_entree' => 'required|integer|min:1',
            'fournisseur'     => 'nullable|string|max:255',
            'date_reception'  => 'required|date',
            'num_ordre'              => 'nullable|string|max:255',
            'id_fournisseur'              => 'nullable|string|max:255',
        ]);

        // Utilisation d'une transaction pour la cohérence des données
        $entry = DB::transaction(function () use ($product, $data, $request) {
            $entry = EntreesStock::create([
                'id_entrees_stocks' => (string) Str::uuid(),
                'id_product'        => $product->id_product,
                'id_users'          => $request->user()->id_users,
                'num_ordre'         => $data['num_ordre'],
                'quantite_entree'   => $data['quantite_entree'],
                'fournisseur'       => $data['id_fournisseur'] ?? null,
                'date_reception'    => $data['date_reception'],
            ]);

            $product->increment('quantite_stock', $data['quantite_entree']);

            return $entry;
        });

        return response()->json($entry, 201);
    }

    /* =======================
        CONFIRME SORTIE DE STOCK
    ======================== */

    /**
     * @OA\Post(
     *     path="/api/sorties/{id}/confirm",
     *     operationId="confirmSortieStock",
     *     tags={"Sorties"},
     *     summary="Confirmer une sortie de stock",
     *     description="Confirme une sortie de stock en attente après validation de la demande et vérification du stock disponible. Réservé à la direction ou à l’administrateur de la même entreprise.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Identifiant UUID de la sortie de stock",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sortie confirmée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Sortie confirmée"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit (entreprise différente ou droits insuffisants)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Sortie non trouvée ou déjà traitée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not Found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=409,
     *         description="Conflit métier (demande non validée ou stock insuffisant)",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Demande non validée")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Stock insuffisant")
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Utilisateur non authentifié"
     *     )
     * )
     */
    public function confirmSortie(Request $request, string $id)
    {
        $user = $request->user();

        $sortie = SortieStock::with(['product', 'demande'])
            ->where('id_sortie_stock', $id)
            ->where('statut_direction', 'EN_ATTENTE')
            ->firstOrFail();

        // 🔐 Multi-entreprise
        if ($sortie->product->id_entreprise !== $user->id_entreprise) {
            abort(403);
        }

        // ❌ Demande non validée
        if ($sortie->demande->statut !== 'VALIDEE') {
            return response()->json([
                'message' => 'Demande non validée'
            ], 409);
        }

        // ❌ Stock insuffisant
        if ($sortie->product->quantite_stock < $sortie->quantite_sortie) {
            return response()->json([
                'message' => 'Stock insuffisant'
            ], 409);
        }

        // 🔥 Décrémenter le stock
        $sortie->product->decrement(
            'quantite_stock',
            $sortie->quantite_sortie
        );

        // ✅ Confirmer la sortie
        $sortie->update([
            'statut_direction' => 'CONFIRMEE',
        ]);

        return response()->json([
            'message' => 'Sortie confirmée',
        ]);
    }



    /**
     * @OA\Post(
     *     path="/api/sorties/{id}/reject",
     *     operationId="rejectSortieStock",
     *     tags={"Sorties"},
     *     summary="Refuser une sortie de stock",
     *     description="Permet à l’Admin ou à la Direction de refuser une sortie de stock en attente pour un produit de la même entreprise.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Identifiant UUID de la sortie de stock",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sortie refusée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Sortie refusée"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit (entreprise différente ou droits insuffisants)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Sortie non trouvée ou déjà traitée",
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
    public function rejectSortie(Request $request, string $id)
    {
        $user = $request->user();

        $sortie = SortieStock::with('product')
            ->where('id_sortie_stock', $id)
            ->where('statut_direction', 'EN_ATTENTE')
            ->firstOrFail();

        if ($sortie->product->id_entreprise !== $user->id_entreprise) {
            abort(403);
        }

        $sortie->update([
            'statut_direction' => 'REFUSEE',
        ]);

        return response()->json([
            'message' => 'Sortie refusée',
        ]);
    }

    
    /* ============================================
        LISTER LES ENTREE ET LES SORTIES DE STOCK
    ============================================ */

    /**
     * @OA\Get(
     * path="/api/products/{id}/entries",
     * tags={"Products"},
     * summary="Lister les entrées de stock d’un produit",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Liste des entrées",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/EntreesStock"))
     * )
     * )
     */
    public function entries(Request $request, string $id)
    {
        $product = Product::where('id_product', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        return EntreesStock::where('id_product', $product->id_product)
            ->orderByDesc('date_reception')
            ->get();
    }

    /**
     * @OA\Get(
     * path="/api/products/{id}/exits",
     * tags={"Products"},
     * summary="Lister les sorties de stock d’un produit",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Liste des sorties",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/SortieStock"))
     * )
     * )
     */
    public function exits(Request $request, string $id)
    {
        $product = Product::where('id_product', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        return SortieStock::where('id_product', $product->id_product)
            ->orderByDesc('date_sortie')
            ->get();
    }

    /**
     * @OA\Get(
     *     path="/api/products/{id}/movements",
     *     tags={"Products"},
     *     summary="Historique des entrées et sorties d’un produit",
     *     description="Retourne la liste des entrées et sorties de stock pour un produit donné",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du produit",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Historique du stock",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="product", ref="#/components/schemas/Product"),
     *             @OA\Property(
     *                 property="entries",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/EntreesStock")
     *             ),
     *             @OA\Property(
     *                 property="exits",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/SortieStock")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Produit non trouvé")
     * )
     */
    public function movements(Request $request, string $id)
    {
        // 🔐 Sécurité multi-tenant
        $product = Product::where('id_product', $id)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        // 📥 Entrées stock
        $entries = EntreesStock::where('id_product', $product->id_product)
            ->orderByDesc('date_reception')
            ->get();

        // 📤 Sorties stock
        $exits = SortieStock::where('id_product', $product->id_product)
            ->orderByDesc('date_sortie')
            ->get();

        return response()->json([
            'product' => $product,
            'entries' => $entries,
            'exits'   => $exits,
        ]);
    }

    /* =======================
        LISTING GLOBAL STOCK
    ======================== */

    /**
     * @OA\Get(
     * path="/api/stock/entries",
     * tags={"Stock Mouvements"},
     * summary="Lister toutes les entrées de stock",
     * description="Récupère l'historique complet des entrées pour l'entreprise connectée, trié par date décroissante.",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Historique des entrées",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * allOf={
     * @OA\Schema(ref="#/components/schemas/EntreesStock"),
     * @OA\Schema(
     * @OA\Property(
     * property="product",
     * ref="#/components/schemas/Product",
     * description="Le produit associé à l'entrée"
     * )
     * )
     * }
     * )
     * )
     * ),
     * @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function listEntries(Request $request)
    {
        // On récupère les entrées dont le produit appartient à l'entreprise de l'user
        $entries = EntreesStock::with('product') // On charge les infos du produit (nom, ref)
            ->whereHas('product', function ($query) use ($request) {
                $query->where('id_entreprise', $request->user()->id_entreprise);
            })
            ->orderBy('date_reception', 'desc')
            ->get();

        return response()->json($entries);
    }

    /**
     * @OA\Get(
     * path="/api/stock/exits",
     * tags={"Stock Mouvements"},
     * summary="Lister toutes les sorties de stock",
     * description="Récupère l'historique complet des sorties pour l'entreprise connectée.",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Historique des sorties",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * allOf={
     * @OA\Schema(ref="#/components/schemas/SortieStock"),
     * @OA\Schema(
     * @OA\Property(
     * property="product",
     * ref="#/components/schemas/Product",
     * description="Le produit associé à la sortie"
     * )
     * )
     * }
     * )
     * )
     * ),
     * @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function listExits(Request $request)
    {
        $exits = SortieStock::with('product')
            ->whereHas('product', function ($query) use ($request) {
                $query->where('id_entreprise', $request->user()->id_entreprise);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($exits);
    }
}
