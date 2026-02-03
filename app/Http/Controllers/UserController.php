<?php

namespace App\Http\Controllers;

use App\Models\Pole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/users",
     *      operationId="getUsersList",
     *      tags={"Users"},
     *      summary="Get list of users",
     *      description="Returns list of all users of the same entreprise",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
     *       )
     * )
     */
    public function index(Request $request)
    {
        return response()->json(
            User::with('profil')
                ->where('id_entreprise', $request->user()->id_entreprise)
                ->get()
        );
    }

    /**
     * @OA\Get(
     *      path="/api/users/{id}",
     *      operationId="getUserById",
     *      tags={"Users"},
     *      summary="Get user by ID",
     *      description="Returns a single user with profile information",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="User ID (UUID)",
     *          required=true,
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *       ),
     *      @OA\Response(response=404, description="User not found")
     * )
     */
    public function show(Request $request, $id)
    {
        $user = User::with('profil')
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        return response()->json($user);
    }

    /**
     * @OA\Post(
     *      path="/api/users",
     *      operationId="storeUser",
     *      tags={"Users"},
     *      summary="Create a new user",
     *      description="Create a new user (Admin only)",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=201,
     *          description="User created successfully",
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *      ),
     *      @OA\Response(response=403, description="Unauthorized"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        if ($request->user()->profil->nom !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|unique:users',
            'password'     => 'required|string|min:8',
            'agence'       => 'nullable|string|max:255',
            'poste'        => 'nullable|string|max:255',
            'profil_id'    => 'required|uuid|exists:profils,id_profil',
            'link_img'     => 'nullable|string|url',
            'matricule'    => 'nullable|string|unique:users,matricule',
            'signature'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'id_users'      => Str::uuid(),
            'id_entreprise' => $request->user()->id_entreprise, // ✅ FIX CRITIQUE
            'name'          => $request->name,
            'email'         => $request->email,
            'phone_number'  => $request->phone_number,
            'password'      => Hash::make($request->password),
            'agence'        => $request->agence,
            'poste'         => $request->poste,
            'profil_id'     => $request->profil_id,
            'link_img'      => $request->link_img,
            'matricule'     => $request->matricule,
            'signature'     => $request->signature,
        ]);

        return response()->json($user->load('profil'), 201);
    }

    /**
     * @OA\Put(
     *      path="/api/users/{id}",
     *      operationId="updateUser",
     *      tags={"Users"},
     *      summary="Update user information",
     *      security={{"sanctum":{}}},
     *      @OA\Response(response=200, description="User updated successfully"),
     *      @OA\Response(response=403, description="Unauthorized"),
     *      @OA\Response(response=404, description="User not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = User::where('id_entreprise', $request->user()->id_entreprise)
            ->find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        if (
            $request->user()->id_users !== $id &&
            $request->user()->profil->nom !== 'Admin'
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->update($request->only([
            'name',
            'email',
            'phone_number',
            'agence',
            'poste',
            'profil_id',
            'link_img',
            'matricule',
            'signature'
        ]));

        return response()->json($user->load('profil'));
    }

    /**
     * @OA\Delete(
     *      path="/api/users/{id}",
     *      operationId="destroyUser",
     *      tags={"Users"},
     *      summary="Delete a user (Admin only)",
     *      security={{"sanctum":{}}},
     *      @OA\Response(response=204, description="User deleted")
     * )
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->profil->nom !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::where('id_entreprise', $request->user()->id_entreprise)
            ->find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        if ($user->link_img && Storage::disk('public')->exists($user->link_img)) {
            Storage::disk('public')->delete($user->link_img);
        }

        $user->delete();

        return response()->noContent();
    }

    /**
     * @OA\Post(
     *      path="/api/users/upload-media",
     *      operationId="uploadUserMedia",
     *      tags={"Users"},
     *      summary="Upload image or file",
     *      description="Upload images to images/ folder and other files to files/ folder",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          content={
     *              @OA\MediaType(
     *                  mediaType="multipart/form-data",
     *                  @OA\Schema(
     *                      required={"file"},
     *                      @OA\Property(
     *                          property="file",
     *                          type="string",
     *                          format="binary",
     *                          description="Image or file to upload"
     *                      )
     *                  )
     *              )
     *          }
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Upload successful",
     *          @OA\JsonContent(
     *              @OA\Property(property="type", type="string", example="image"),
     *              @OA\Property(property="path", type="string", example="/storage/images/abc123.png"),
     *              @OA\Property(property="original_name", type="string", example="photo.png")
     *          )
     *      ),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function uploadMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');

        // Déterminer le type
        $isImage = str_starts_with($file->getMimeType(), 'image/');

        $folder = $isImage ? 'images' : 'files';

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs(
            'public/' . $folder,
            $filename
        );

        return response()->json([
            'type' => $isImage ? 'image' : 'file',
            'path' => '/storage/' . $folder . '/' . $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}/assign-pole",
     *     operationId="assignPole",
     *     tags={"Users"},
     *     summary="Assigner un pôle à un utilisateur",
     *     description="Permet à un administrateur d’assigner ou de changer le pôle d’un utilisateur.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Identifiant UUID de l'utilisateur",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id_pole"},
     *             @OA\Property(property="id_pole", type="string", format="uuid", example="dff18e56-c7eb-4e9c-9fc2-91e3f2d059ac")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pôle assigné avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Pôle assigné à l'utilisateur")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur ou pôle non trouvé"
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Action interdite"
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function assignPole(Request $request, string $id)
    {
        $user = $request->user();

        // Vérifier que seul un admin peut assigner un pôle
        if ($user->profil->nom !== 'Admin') {
            return response()->json(['message' => 'Accès interdit'], 403);
        }

        $request->validate([
            'id_pole' => 'required|uuid|exists:poles,id_pole',
        ]);

        $targetUser = User::where('id_users', $id)->first();

        if (!$targetUser) {
            return response()->json(['message' => 'Utilisateur introuvable'], 404);
        }

        $pole = Pole::where('id_pole', $request->id_pole)->first();

        if (!$pole) {
            return response()->json(['message' => 'Pôle introuvable'], 404);
        }

        // Mise à jour de l'utilisateur
        $targetUser->update([
            'id_pole' => $pole->id_pole,
            'agence' => $pole->nom,
        ]);

        return response()->json([
            'message' => 'Pôle assigné à l\'utilisateur',
            'user' => $targetUser
        ], 200);
    }
}
