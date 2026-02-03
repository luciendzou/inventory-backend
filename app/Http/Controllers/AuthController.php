<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/login",
     *      tags={"Authentication"},
     *      summary="Connexion utilisateur",
     *      description="Retourne l'utilisateur et un token Sanctum",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"login","password"},
     *              @OA\Property(property="login", type="string", example="user@email.com"),
     *              @OA\Property(property="password", type="string", example="password123")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Connexion réussie",
     *          @OA\JsonContent(
     *              @OA\Property(property="access_token", type="string"),
     *              @OA\Property(property="token_type", type="string", example="Bearer"),
     *              @OA\Property(property="user", ref="#/components/schemas/User")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Identifiants invalides"
     *      )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'phone_number';

        $user = User::where($field, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants incorrects'], 401);
        }

        return response()->json([
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type'   => 'Bearer',
            'user'         => $user->load(['profil', 'entreprise'])
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/register",
     *      tags={"Authentication"},
     *      summary="Créer un utilisateur",
     *      description="Crée un utilisateur lié à une entreprise",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name","email","phone_number","password","password_confirmation","id_entreprise"},
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string", format="email"),
     *              @OA\Property(property="phone_number", type="string"),
     *              @OA\Property(property="password", type="string"),
     *              @OA\Property(property="password_confirmation", type="string"),
     *              @OA\Property(property="id_entreprise", type="string", format="uuid"),
     *              @OA\Property(property="profil_id", type="string", format="uuid")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Utilisateur créé avec succès",
     *          @OA\JsonContent(
     *              @OA\Property(property="access_token", type="string"),
     *              @OA\Property(property="token_type", type="string"),
     *              @OA\Property(property="user", ref="#/components/schemas/User")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Erreur de validation"
     *      )
     * )
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'phone_number'   => 'required|string|unique:users,phone_number',
            'password'       => 'required|string|min:8|confirmed',
            'id_entreprise'  => 'required|uuid|exists:entreprises,id_entreprise',
            'profil_id'      => 'nullable|uuid|exists:profils,id_profil',
            'agence'         => 'nullable|string',
            'poste'          => 'nullable|string',
            'matricule'      => 'nullable|string|unique:users,matricule',
        ]);

        return DB::transaction(function () use ($data) {

            $user = User::create([
                'id_users'      => (string) Str::uuid(),
                'id_entreprise' => $data['id_entreprise'],
                'name'          => $data['name'],
                'email'         => $data['email'],
                'phone_number'  => $data['phone_number'],
                'password'      => Hash::make($data['password']),
                'profil_id'     => $data['profil_id'] ?? config('app.default_profil_id', '5'),
                'agence'        => $data['agence'] ?? null,
                'poste'         => $data['poste'] ?? null,
                'matricule'     => $data['matricule'] ?? null,
            ]);

            return response()->json([
                'access_token' => $user->createToken('auth_token')->plainTextToken,
                'token_type'   => 'Bearer',
                'user'         => $user->load(['profil', 'entreprise']),
            ], 201);
        });
    }

    /**
     * @OA\Post(
     *      path="/api/logout",
     *      tags={"Authentication"},
     *      summary="Déconnexion",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Déconnexion réussie"
     *      )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }
}
