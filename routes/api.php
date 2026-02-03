<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ConfigEntrepriseController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\EntrepriseController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\PoleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes publiques (sans authentification)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Routes pour entreprises
Route::post('/entreprises', [EntrepriseController::class, 'store']);
Route::get('/entreprises/{id}', [EntrepriseController::class, 'show']);

// Routes protégées (avec authentification)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);


    // Routes pour entreprises
    Route::get('/entreprises', [EntrepriseController::class, 'index']);
    Route::put('/entreprises/{id}', [EntrepriseController::class, 'update']);
    Route::delete('/entreprises/{id}', [EntrepriseController::class, 'destroy']);
    Route::apiResource('config-entreprises', ConfigEntrepriseController::class)->withoutMiddleware('auth:sanctum');
    Route::get(
        '/config-entreprises/by-entreprise/{idEntreprise}',
        [ConfigEntrepriseController::class, 'showByEntreprise']
    )->withoutMiddleware('auth:sanctum');

    // Gestion des utilisateurs
    Route::apiResource('users', UserController::class);
    Route::post('/users/{id}/profile-picture', [UserController::class, 'uploadProfilePicture']);
    Route::post('/users/{id}/change-password', [UserController::class, 'changePassword']);
    Route::put('/users/{id}/assign-pole', [UserController::class, 'assignPole']);

    // Gestion des produits
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Stock
    Route::post('products/{id}/entries', [ProductController::class, 'storeEntry']);
    Route::post('products/{id}/exits', [ProductController::class, 'storeExit']);
    Route::post('sorties/{id}/confirm', [ProductController::class, 'confirmExit']);
    Route::get('/products/{id}/entries', [ProductController::class, 'entries']);
    Route::get('/products/{id}/exits', [ProductController::class, 'exits']);
    Route::get('/products/{id}/movements', [ProductController::class, 'movements']);

    Route::post('/sorties/{id}/confirm', [ProductController::class, 'confirmSortie']);
    Route::post('/sorties/{id}/reject', [ProductController::class, 'rejectSortie']);

    // Liste de toutes les entrées -> GET /api/stock/entries
    Route::get('stock/entries', [ProductController::class, 'listEntries']);

    // Liste de toutes les sorties -> GET /api/stock/exits
    Route::get('stock/exits', [ProductController::class, 'listExits']);

    // Gestion des profils
    Route::get('/profils', [ProfileController::class, 'index'])->withoutMiddleware('auth:sanctum');
    Route::get('/profils/{id}', [ProfileController::class, 'show'])->withoutMiddleware('auth:sanctum');
    Route::post('/profils', [ProfileController::class, 'store']);
    Route::put('/profils/{id}', [ProfileController::class, 'update']);
    Route::delete('/profils/{id}', [ProfileController::class, 'destroy']);

    // Gestions des Categories
    Route::get('/categories', [CategorieController::class, 'index']);
    Route::get('/categories/{id}', [CategorieController::class, 'show']);
    Route::apiResource('categories', CategorieController::class)->except(['index', 'show']);
    Route::post('/categories', [CategorieController::class, 'store']);
    Route::put('/categories/{id}', [CategorieController::class, 'update']);
    Route::delete('/categories/{id}', [CategorieController::class, 'destroy']);

    // Gestions des Brands
    Route::apiResource('brands', BrandController::class);

    // Gestions des Fournisseurs
    Route::apiResource('fournisseurs', FournisseurController::class);

    //Gestion des demandes
    Route::get('/demandes', [DemandeController::class, 'index']);
    Route::get('/demandes/me', [DemandeController::class, 'myDemandes']);
    Route::post('/demandes', [DemandeController::class, 'store']);
    Route::get('/demandes/{id}', [DemandeController::class, 'show']);
    Route::put('/demandes/{id}', [DemandeController::class, 'update']);
    Route::delete('/demandes/{id}', [DemandeController::class, 'destroy']);
    Route::post('/demandes/{id}/validate', [DemandeController::class, 'validateDemande']);
    Route::post('/demandes/{id}/reject', [DemandeController::class, 'rejectDemande']);
    Route::get('/demandes/{id}/sorties', [DemandeController::class, 'sorties']);

    Route::get('/poles', [PoleController::class, 'index']);
    Route::post('/poles', [PoleController::class, 'store']);
    Route::get('/poles/{id}', [PoleController::class, 'show']);
    Route::put('/poles/{id}', [PoleController::class, 'update']);
    Route::delete('/poles/{id}', [PoleController::class, 'destroy']);

});

Route::middleware('auth:sanctum')->post(
    '/users/upload-media',
    [UserController::class, 'uploadMedia']
);
