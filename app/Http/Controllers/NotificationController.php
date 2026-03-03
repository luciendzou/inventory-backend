<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
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

    public function unreadCount(Request $request)
    {
        $count = Notification::query()
            ->where('id_users', $request->user()->id_users)
            ->where('id_entreprise', $request->user()->id_entreprise)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

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

