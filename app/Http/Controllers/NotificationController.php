<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="Notifications utilisateur"
 * )
 */
class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     tags={"Notifications"},
     *     summary="Lister mes notifications",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="unread",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", example="DEMANDE_VALIDEE")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste paginee",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Notification")
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=42)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'unread' => 'nullable|boolean',
            'type' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);
        $query = Notification::query()
            ->where('id_users', $request->user()->id_users)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->orderByDesc('created_at');

        if (array_key_exists('unread', $data)) {
            $query->where('is_read', filter_var($data['unread'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($data['type'])) {
            $query->where('type', $data['type']);
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/unread-count",
     *     tags={"Notifications"},
     *     summary="Compter les notifications non lues",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Compteur",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="count", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifie")
     * )
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::query()
            ->where('id_users', $request->user()->id_users)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * @OA\Patch(
     *     path="/api/notifications/{id}/read",
     *     tags={"Notifications"},
     *     summary="Marquer une notification comme lue",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification mise a jour",
     *         @OA\JsonContent(ref="#/components/schemas/Notification")
     *     ),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=404, description="Notification introuvable")
     * )
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = Notification::query()
            ->where('id_notification', $id)
            ->where('id_users', $request->user()->id_users)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json($notification->fresh());
    }

    /**
     * @OA\Patch(
     *     path="/api/notifications/read-all",
     *     tags={"Notifications"},
     *     summary="Marquer toutes mes notifications comme lues",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Nombre de notifications mises a jour",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="updated", type="integer", example=8)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifie")
     * )
     */
    public function markAllAsRead(Request $request)
    {
        $updated = Notification::query()
            ->where('id_users', $request->user()->id_users)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['updated' => $updated]);
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/{id}",
     *     tags={"Notifications"},
     *     summary="Supprimer une notification",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=204, description="Supprimee"),
     *     @OA\Response(response=401, description="Non authentifie"),
     *     @OA\Response(response=404, description="Notification introuvable")
     * )
     */
    public function destroy(Request $request, string $id)
    {
        $notification = Notification::query()
            ->where('id_notification', $id)
            ->where('id_users', $request->user()->id_users)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->firstOrFail();

        $notification->delete();

        return response()->noContent();
    }
}
