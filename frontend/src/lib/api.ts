import { ApiError, type ApiErrorPayload } from './errors';

const API_BASE = '/api/v1';

let csrfPromise: Promise<void> | null = null;
let unauthorizedHandler: (() => void) | null = null;

export function setUnauthorizedHandler(handler: (() => void) | null): void {
    unauthorizedHandler = handler;
}

function readCookie(name: string): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

async function ensureCsrf(): Promise<void> {
    if (csrfPromise) {
        return csrfPromise;
    }

    csrfPromise = fetch('/sanctum/csrf-cookie', {
        credentials: 'include',
        headers: { Accept: 'application/json' },
    }).then((response) => {
        if (!response.ok) {
            throw new ApiError(response.status, {
                message: 'Tidak dapat memulai sesi.',
            });
        }
    });

    return csrfPromise;
}

export async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
    const method = (init.method ?? 'GET').toUpperCase();

    if (method !== 'GET' && method !== 'HEAD') {
        await ensureCsrf();
    }

    const headers = new Headers(init.headers);
    headers.set('Accept', 'application/json');

    if (
        init.body &&
        typeof FormData !== 'undefined' &&
        !(init.body instanceof FormData)
    ) {
        headers.set('Content-Type', 'application/json');
    }

    const token = readCookie('XSRF-TOKEN');

    if (token) {
        headers.set('X-XSRF-TOKEN', token);
    }

    const response = await fetch(`${API_BASE}${path}`, {
        ...init,
        headers,
        credentials: 'include',
    });

    if (!response.ok) {
        let payload: ApiErrorPayload = {};

        try {
            payload = (await response.json()) as ApiErrorPayload;
        } catch {
            payload = {};
        }

        if (response.status === 401) {
            unauthorizedHandler?.();
        }

        throw new ApiError(response.status, payload);
    }

    if (response.status === 204) {
        return undefined as T;
    }

    return (await response.json()) as T;
}
