# Realtime Notifications (Vue + Laravel Echo)

Ce backend diffuse les nouvelles notifications sur le canal prive:

- `private-user.{id_users}`
- event: `.notification.created`

## 1) Variables frontend

Dans ton frontend, configure:

```env
VITE_PUSHER_APP_KEY=your_key
VITE_PUSHER_HOST=your_host
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https
VITE_PUSHER_APP_CLUSTER=mt1
VITE_API_BASE_URL=https://api.inventory.cremin-cam.org
```

## 2) Installer Echo

```bash
npm i laravel-echo pusher-js
```

## 3) Initialiser Echo

Exemple `src/lib/echo.ts`:

```ts
import Echo from "laravel-echo";
import Pusher from "pusher-js";

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

window.Pusher = Pusher;

export function makeEcho(token: string) {
  return new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    wsHost: import.meta.env.VITE_PUSHER_HOST,
    wsPort: Number(import.meta.env.VITE_PUSHER_PORT || 80),
    wssPort: Number(import.meta.env.VITE_PUSHER_PORT || 443),
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || "https") === "https",
    enabledTransports: ["ws", "wss"],
    authEndpoint: `${import.meta.env.VITE_API_BASE_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
    },
  });
}
```

## 4) S'abonner au canal utilisateur

Exemple dans un store/composable:

```ts
import { makeEcho } from "@/lib/echo";

let echo: any;

export function startNotificationsRealtime(userId: string, token: string, onIncoming: (payload: any) => void) {
  echo = makeEcho(token);

  echo.private(`user.${userId}`)
    .listen(".notification.created", (payload: any) => {
      onIncoming(payload);
    });
}

export function stopNotificationsRealtime(userId: string) {
  if (!echo) return;
  echo.leave(`private-user.${userId}`);
  echo.disconnect();
  echo = null;
}
```

## 5) Exemples d'usage UI

- incrementer badge unread
- prepend dans la liste notifications
- afficher toast

## 6) Fallback recommande

Garder un polling de secours:

- `GET /api/notifications/unread-count` toutes les 20-30s

Ainsi, meme si websocket indisponible, l'UX reste correcte.

