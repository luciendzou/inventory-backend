<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationService
{
    public function sendToUser(
        string $userId,
        string $entrepriseId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        return Notification::create([
            'id_notification' => (string) Str::uuid(),
            'id_users' => $userId,
            'id_entreprise' => $entrepriseId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ?: null,
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function sendToRoleInEntreprise(
        string $entrepriseId,
        string $roleName,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): int {
        $users = User::query()
            ->with('profil')
            ->where('id_entreprise', $entrepriseId)
            ->get()
            ->filter(fn (User $u) => $u->profil?->nom === $roleName);

        return $this->sendToUsers($users, $entrepriseId, $type, $title, $message, $data);
    }

    public function sendToUsers(
        Collection $users,
        string $entrepriseId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): int {
        $rows = [];
        $now = now();
        foreach ($users as $user) {
            $rows[] = [
                'id_notification' => (string) Str::uuid(),
                'id_users' => $user->id_users,
                'id_entreprise' => $entrepriseId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data ?: null,
                'is_read' => false,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        Notification::insert($rows);
        return count($rows);
    }
}

