<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\Product;
use App\Models\EntreesStock;
use App\Models\SortieStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
     * summary="Créer un produit ou alimenter un existant (Admin)",
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
     * @OA\Property(property="conditionnement", type="string", example="carton"),
     * @OA\Property(property="reference", type="string", example="REF-001"),
     * @OA\Property(property="agence", type="string", example="Siège"),
     * @OA\Property(
     *     property="on_existing",
     *     type="string",
     *     enum={"reject","increment","replace"},
     *     example="increment",
     *     description="Comportement si produit existant: reject=409, increment=ajout stock, replace=maj complète"
     * ),
     * @OA\Property(property="date_reception", type="string", format="date", example="2026-02-20"),
     * @OA\Property(property="num_ordre", type="string", example="ORD-2026-002")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Produit créé",
     * @OA\JsonContent(ref="#/components/schemas/Product")
     * ),
     * @OA\Response(
     * response=200,
     * description="Produit existant mis à jour/stock incrémenté",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="message", type="string", example="Stock ajouté sur produit existant"),
     * @OA\Property(property="product", ref="#/components/schemas/Product")
     * )
     * ),
     * @OA\Response(response=409, description="Produit existant et on_existing=reject"),
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
            'conditionnement'     => 'nullable|string|max:50',
            'reference'           => 'nullable|string|max:255',
            'agence'              => 'nullable|string|max:255',
            'id_fournisseur'              => 'nullable|string|max:255',
            'id_categorie'              => 'nullable|string|max:255',
            'id_marque'              => 'nullable|string|max:255',
            'on_existing'         => 'nullable|in:reject,increment,replace',
            'date_reception'      => 'nullable|date',
            'num_ordre'           => 'nullable|string|max:255',
        ]);

        $existingMode = $data['on_existing'] ?? 'reject';
        unset($data['on_existing']);

        $existing = $this->findExistingProduct($request->user()->id_entreprise, $data['reference'] ?? null, $data['nom'] ?? null);
        if ($existing) {
            if ($existingMode === 'reject') {
                return response()->json([
                    'message' => 'Produit existant. Utilisez on_existing=increment ou on_existing=replace',
                    'product_id' => $existing->id_product,
                ], 409);
            }

            if ($existingMode === 'replace') {
                $existing->update([
                    'id_categorie' => $data['id_categorie'] ?? $existing->id_categorie,
                    'id_marque' => $data['id_marque'] ?? $existing->id_marque,
                    'id_fournisseur' => $data['id_fournisseur'] ?? $existing->id_fournisseur,
                    'nom' => $data['nom'] ?? $existing->nom,
                    'description' => array_key_exists('description', $data) ? $data['description'] : $existing->description,
                    'reference' => array_key_exists('reference', $data) ? $data['reference'] : $existing->reference,
                    'quantite_stock' => (int) $data['quantite_stock'],
                    'prix' => array_key_exists('prix', $data) ? (float) $data['prix'] : $existing->prix,
                    'quantite_min_alerte' => array_key_exists('quantite_min_alerte', $data) ? (int) $data['quantite_min_alerte'] : $existing->quantite_min_alerte,
                    'conditionnement' => array_key_exists('conditionnement', $data) ? $data['conditionnement'] : $existing->conditionnement,
                    'agence' => array_key_exists('agence', $data) ? $data['agence'] : $existing->agence,
                ]);

                return response()->json([
                    'message' => 'Produit existant mis à jour (mode replace)',
                    'product' => $existing->fresh(),
                ]);
            }

            $qty = (int) $data['quantite_stock'];
            if ($qty <= 0) {
                return response()->json([
                    'message' => 'quantite_stock doit être > 0 en mode increment',
                ], 422);
            }

            DB::transaction(function () use ($existing, $request, $data, $qty) {
                $existing->increment('quantite_stock', $qty);

                EntreesStock::create([
                    'id_entrees_stocks' => (string) Str::uuid(),
                    'id_product' => $existing->id_product,
                    'id_users' => $request->user()->id_users,
                    'num_ordre' => $data['num_ordre'] ?? null,
                    'quantite_entree' => $qty,
                    'fournisseur' => $data['id_fournisseur'] ?? null,
                    'date_reception' => $data['date_reception'] ?? now()->toDateString(),
                ]);

                $existing->update([
                    'prix' => array_key_exists('prix', $data) ? (float) $data['prix'] : $existing->prix,
                    'quantite_min_alerte' => array_key_exists('quantite_min_alerte', $data) ? (int) $data['quantite_min_alerte'] : $existing->quantite_min_alerte,
                    'conditionnement' => array_key_exists('conditionnement', $data) ? $data['conditionnement'] : $existing->conditionnement,
                    'description' => array_key_exists('description', $data) ? $data['description'] : $existing->description,
                    'agence' => array_key_exists('agence', $data) ? $data['agence'] : $existing->agence,
                    'id_marque' => $data['id_marque'] ?? $existing->id_marque,
                    'id_fournisseur' => $data['id_fournisseur'] ?? $existing->id_fournisseur,
                    'id_categorie' => $data['id_categorie'] ?? $existing->id_categorie,
                ]);
            });

            return response()->json([
                'message' => 'Stock ajouté sur produit existant',
                'product' => $existing->fresh(),
            ]);
        }

        $data['id_product']    = (string) Str::uuid();
        $data['id_entreprise'] = $request->user()->id_entreprise;
        $data['id_users']      = $request->user()->id_users;
        unset($data['date_reception'], $data['num_ordre']);

        return response()->json(Product::create($data), 201);
    }

    /**
     * @OA\Post(
     *     path="/api/products/import-csv",
     *     tags={"Products"},
     *     summary="Importer des produits depuis un fichier CSV",
     *     description="Importe des produits via un fichier CSV (admin uniquement).",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="multipart/form-data",
     *                 @OA\Schema(
     *                     required={"file"},
     *                     @OA\Property(
     *                         property="file",
     *                         type="string",
     *                         format="binary",
     *                         description="Fichier CSV a importer"
     *                     ),
     *                     @OA\Property(
     *                         property="on_existing",
     *                         type="string",
     *                         enum={"replace","increment"},
     *                         example="increment",
     *                         description="Comportement si le produit existe déjà"
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Import termine",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Import CSV termine"),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="created", type="integer", example=12),
     *                 @OA\Property(property="updated", type="integer", example=3),
     *                 @OA\Property(
     *                     property="headers_detected",
     *                     type="array",
     *                     @OA\Items(type="string", example="id_product")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=403, description="Acces refuse"),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Erreurs de validation dans le fichier CSV"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=500, description="Erreur interne pendant l'import")
     * )
     *
     * Import CSV de produits.
     * Colonnes attendues (entetes): nom, id_categorie.
     * Colonnes utiles:
     * - id_product (PK produit, UUID)
     * - description, reference, quantite_stock, prix, quantite_min_alerte, conditionnement,
     *   agence, id_fournisseur, id_marque, is_direction
     */
    public function importCsv(Request $request)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Acces refuse'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'on_existing' => 'nullable|in:replace,increment',
        ]);
        $onExisting = $request->input('on_existing', 'replace');

        $file = $request->file('file');
        [$rows, $headerMap] = $this->parseCsvFile($file);

        if (empty($rows)) {
            return response()->json([
                'message' => 'Le fichier CSV est vide',
            ], 422);
        }

        $entrepriseId = $request->user()->id_entreprise;
        $userId = $request->user()->id_users;

        $created = 0;
        $updated = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $line => $row) {
                $validator = validator($row, [
                    'nom' => 'required|string|max:255',
                    'id_categorie' => 'required|uuid|exists:categories,id_categorie',
                    'id_marque' => 'nullable|uuid|exists:brands,id_marque',
                    'id_fournisseur' => 'nullable|uuid|exists:fournisseurs,id_fournisseur',
                    'reference' => 'nullable|string|max:255',
                    'description' => 'nullable|string',
                    'quantite_stock' => 'nullable|integer|min:0',
                    'prix' => 'nullable|numeric|min:0',
                    'quantite_min_alerte' => 'nullable|integer|min:0',
                    'conditionnement' => 'nullable|string|max:50',
                    'agence' => 'nullable|string|max:255',
                    'is_direction' => 'nullable',
                    'id_product' => 'nullable|uuid',
                ]);

                if ($validator->fails()) {
                    $errors[] = [
                        'line' => $line,
                        'errors' => $validator->errors(),
                    ];
                    continue;
                }

                $rawIdProduct = trim((string) ($row['id_product'] ?? ''));

                $payload = [
                    'id_entreprise' => $entrepriseId,
                    'id_users' => $userId,
                    'id_categorie' => $row['id_categorie'],
                    'id_marque' => $row['id_marque'] ?: null,
                    'id_fournisseur' => $row['id_fournisseur'] ?: null,
                    'nom' => $row['nom'],
                    'description' => $row['description'] ?: null,
                    'reference' => $row['reference'] ?: null,
                    'quantite_stock' => (int) ($row['quantite_stock'] ?: 0),
                    'prix' => (float) ($row['prix'] ?: 0),
                    'quantite_min_alerte' => (int) ($row['quantite_min_alerte'] ?: 0),
                    'conditionnement' => $row['conditionnement'] ?: null,
                    'is_direction' => $this->toBool($row['is_direction'] ?? false),
                    'agence' => $row['agence'] ?: null,
                ];

                $product = null;
                if ($rawIdProduct !== '' && Str::isUuid($rawIdProduct)) {
                    $product = Product::where('id_entreprise', $entrepriseId)
                        ->where('id_product', $rawIdProduct)
                        ->first();
                }

                if (!$product) {
                    $product = $this->findExistingProduct($entrepriseId, $payload['reference'] ?? null, $payload['nom'] ?? null);
                }

                if ($product) {
                    if ($onExisting === 'increment') {
                        $qty = (int) $payload['quantite_stock'];
                        if ($qty > 0) {
                            $product->increment('quantite_stock', $qty);

                            EntreesStock::create([
                                'id_entrees_stocks' => (string) Str::uuid(),
                                'id_product' => $product->id_product,
                                'id_users' => $userId,
                                'num_ordre' => null,
                                'quantite_entree' => $qty,
                                'fournisseur' => $payload['id_fournisseur'],
                                'date_reception' => now()->toDateString(),
                            ]);
                        }

                        $product->update([
                            'prix' => $payload['prix'],
                            'quantite_min_alerte' => $payload['quantite_min_alerte'],
                            'conditionnement' => $payload['conditionnement'],
                            'description' => $payload['description'],
                            'agence' => $payload['agence'],
                            'id_marque' => $payload['id_marque'],
                            'id_fournisseur' => $payload['id_fournisseur'],
                            'id_categorie' => $payload['id_categorie'],
                        ]);
                    } else {
                        $product->update($payload);
                    }
                    $updated++;
                } else {
                    $productId = null;
                    if ($rawIdProduct !== '' && Str::isUuid($rawIdProduct)) {
                        $alreadyUsed = Product::where('id_product', $rawIdProduct)->exists();
                        $productId = $alreadyUsed ? (string) Str::uuid() : $rawIdProduct;
                    } else {
                        $productId = (string) Str::uuid();
                    }

                    $product = Product::create(array_merge($payload, [
                        'id_product' => $productId,
                    ]));
                    $created++;
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Erreurs de validation dans le fichier CSV',
                    'errors' => $errors,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'message' => 'Import CSV termine',
                'summary' => [
                    'created' => $created,
                    'updated' => $updated,
                    'headers_detected' => $headerMap,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => "Erreur pendant l'import CSV",
                'error' => $e->getMessage(),
            ], 500);
        }
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
     * @OA\Property(property="conditionnement", type="string"),
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
            'conditionnement'     => 'nullable|string|max:50',
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

    /**
     * @OA\Post(
     * path="/api/stock/year-rollover",
     * tags={"Stock Mouvements"},
     * summary="Basculer le stock de fin d'annee vers la nouvelle annee",
     * description="Cree (ou met a jour) une entree d'ouverture au 1er janvier pour chaque produit avec le stock courant.",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=false,
     * @OA\JsonContent(
     * @OA\Property(property="year", type="integer", example=2027, description="Annee cible (par defaut: annee courante)")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Bascule terminee",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="message", type="string", example="Bascule de stock effectuee"),
     * @OA\Property(property="year", type="integer", example=2027),
     * @OA\Property(property="opening_date", type="string", example="2027-01-01"),
     * @OA\Property(property="created_entries", type="integer", example=25),
     * @OA\Property(property="updated_entries", type="integer", example=3),
     * @OA\Property(property="total_products", type="integer", example=40)
     * )
     * ),
     * @OA\Response(response=403, description="Acces refuse"),
     * @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function yearRollover(Request $request)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Acces refuse'], 403);
        }

        $data = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $year = (int) ($data['year'] ?? now()->year);
        $openingDate = Carbon::create($year, 1, 1)->toDateString();
        $numOrdre = 'OUVERTURE-' . $year;
        $entrepriseId = $request->user()->id_entreprise;
        $userId = $request->user()->id_users;

        $created = 0;
        $updated = 0;

        DB::transaction(function () use (
            $entrepriseId,
            $userId,
            $openingDate,
            $numOrdre,
            &$created,
            &$updated
        ) {
            $products = Product::where('id_entreprise', $entrepriseId)->get();

            foreach ($products as $product) {
                $qty = (int) $product->quantite_stock;
                if ($qty <= 0) {
                    continue;
                }

                $existingOpening = EntreesStock::where('id_product', $product->id_product)
                    ->where('num_ordre', $numOrdre)
                    ->whereDate('date_reception', $openingDate)
                    ->first();

                if ($existingOpening) {
                    $existingOpening->update([
                        'id_users' => $userId,
                        'quantite_entree' => $qty,
                        'fournisseur' => 'BASCULE-ANNUELLE',
                        'date_reception' => $openingDate,
                    ]);
                    $updated++;
                    continue;
                }

                EntreesStock::create([
                    'id_entrees_stocks' => (string) Str::uuid(),
                    'id_product' => $product->id_product,
                    'id_users' => $userId,
                    'num_ordre' => $numOrdre,
                    'quantite_entree' => $qty,
                    'fournisseur' => 'BASCULE-ANNUELLE',
                    'date_reception' => $openingDate,
                ]);
                $created++;
            }
        });

        $totalProducts = Product::where('id_entreprise', $entrepriseId)->count();

        return response()->json([
            'message' => 'Bascule de stock effectuee',
            'year' => $year,
            'opening_date' => $openingDate,
            'created_entries' => $created,
            'updated_entries' => $updated,
            'total_products' => $totalProducts,
        ]);
    }

    private function parseCsvFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier CSV");
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = $this->detectDelimiter($firstLine ?: '');

        $rawHeaders = fgetcsv($handle, 0, $delimiter);
        if (!$rawHeaders) {
            fclose($handle);
            return [[], []];
        }

        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $rawHeaders);
        $headerMap = [];
        foreach ($headers as $h) {
            $headerMap[] = $h;
        }

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $idx => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = isset($data[$idx]) ? trim((string) $data[$idx]) : null;
            }

            $mapped = [
                'id_product' => $assoc['id_product'] ?? null,
                'nom' => $assoc['nom'] ?? $assoc['name'] ?? null,
                'description' => $assoc['description'] ?? null,
                'reference' => $assoc['reference'] ?? null,
                'id_categorie' => $assoc['id_categorie'] ?? $assoc['categorie_id'] ?? null,
                'id_marque' => $assoc['id_marque'] ?? $assoc['marque_id'] ?? null,
                'id_fournisseur' => $assoc['id_fournisseur'] ?? $assoc['fournisseur_id'] ?? null,
                'quantite_stock' => $assoc['quantite_stock'] ?? $assoc['stock'] ?? null,
                'prix' => $assoc['prix'] ?? $assoc['price'] ?? null,
                'quantite_min_alerte' => $assoc['quantite_min_alerte'] ?? $assoc['quantite_min'] ?? null,
                'conditionnement' => $assoc['conditionnement'] ?? $assoc['packaging'] ?? null,
                'is_direction' => $assoc['is_direction'] ?? null,
                'agence' => $assoc['agence'] ?? null,
            ];

            $rows[$line] = $mapped;
        }

        fclose($handle);

        return [$rows, $headerMap];
    }

    private function detectDelimiter(string $sample): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $maxCount = -1;
        $best = ',';

        foreach ($delimiters as $delimiter) {
            $count = count(str_getcsv($sample, $delimiter));
            if ($count > $maxCount) {
                $maxCount = $count;
                $best = $delimiter;
            }
        }

        return $best;
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = str_replace(["\n", "\r", "\t"], ' ', $header);
        $header = preg_replace('/\s+/', '_', $header);

        $map = [
            'compte_budgetaire' => 'compte_budgetaire',
            'imputation_budgetaire' => 'imputation_budgetaire',
            'reference_op' => 'reference_op',
        ];

        return $map[$header] ?? $header;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = mb_strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'oui', 'y'], true);
    }

    private function findExistingProduct(string $entrepriseId, ?string $reference, ?string $nom): ?Product
    {
        if (!empty($reference)) {
            $product = Product::where('id_entreprise', $entrepriseId)
                ->where('reference', $reference)
                ->first();
            if ($product) {
                return $product;
            }
        }

        if (!empty($nom)) {
            return Product::where('id_entreprise', $entrepriseId)
                ->where('nom', $nom)
                ->first();
        }

        return null;
    }
}
