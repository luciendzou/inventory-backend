<?php

namespace App\Http\Controllers;

use App\Models\Profil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Profiles",
 *     description="API Endpoints for managing user profiles"
 * )
 */
class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/profils",
     *      tags={"Profiles"},
     *      summary="Get list of profiles",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Profil"))
     *      ),
     *      @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index()
    {
        return Profil::all();
    }

    /**
     * @OA\Post(
     *      path="/api/profils",
     *      tags={"Profiles"},
     *      summary="Create a new profile",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"nom"},
     *              @OA\Property(property="nom", type="string", example="Moderator"),
     *              @OA\Property(property="description", type="string", example="Moderates content")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Profile created successfully",
     *          @OA\JsonContent(ref="#/components/schemas/Profil")
     *      ),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:50|unique:profils,nom',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profil = Profil::create([
            'id_profil'   => Str::uuid(),
            'nom'         => $request->nom,
            'description' => $request->description,
        ]);

        return response()->json($profil, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/profils/{id}",
     *      tags={"Profiles"},
     *      summary="Get profile information",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Profil")
     *      ),
     *      @OA\Response(response=404, description="Profile not found")
     * )
     */
    public function show(Profil $profil)
    {
        return $profil;
    }

    /**
     * @OA\Put(
     *      path="/api/profils/{id}",
     *      tags={"Profiles"},
     *      summary="Update existing profile",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="nom", type="string"),
     *              @OA\Property(property="description", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Profile updated",
     *          @OA\JsonContent(ref="#/components/schemas/Profil")
     *      )
     * )
     */
    public function update(Request $request, Profil $profil)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:50|unique:profils,nom,' . $profil->id_profil . ',id_profil',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profil->update($request->all());

        return response()->json($profil);
    }

    /**
     * @OA\Delete(
     *      path="/api/profils/{id}",
     *      tags={"Profiles"},
     *      summary="Delete existing profile",
     *      security={{"sanctum":{}}},
     *      @OA\Response(response=204, description="Profile deleted")
     * )
     */
    public function destroy(Profil $profil)
    {
        $profil->delete();
        return response()->noContent();
    }
}
