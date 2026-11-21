import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const appKey = import.meta.env.VITE_REVERB_APP_KEY as string | undefined;

if (appKey) {
    (window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher;
}

export const echo = appKey
    ? new Echo({
          broadcaster: 'reverb',
          key: appKey,
          wsHost:
              (import.meta.env.VITE_REVERB_HOST as string | undefined) ??
              window.location.hostname,
          wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8084),
          wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8084),
          forceTLS: (import.meta.env.VITE_REVERB_SCHEME as string | undefined) === 'https',
          enabledTransports: ['ws', 'wss'],
          authEndpoint: '/broadcasting/auth',
          withCredentials: true,
      })
    : null;
