import { ApiError, type ApiErrorPayload } from './errors';

export const API_BASE = '/api/v1';

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

function buildHeaders(initHeaders: HeadersInit | undefined, accept: string): Headers {
    const headers = new Headers(initHeaders);
    headers.set('Accept', accept);

    const token = readCookie('XSRF-TOKEN');

    if (token) {
        headers.set('X-XSRF-TOKEN', token);
    }

    return headers;
}

async function throwResponseError(response: Response): Promise<never> {
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

export async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
    const method = (init.method ?? 'GET').toUpperCase();

    if (method !== 'GET' && method !== 'HEAD') {
        await ensureCsrf();
    }

    const headers = buildHeaders(init.headers, 'application/json');

    if (
        init.body &&
        typeof FormData !== 'undefined' &&
        !(init.body instanceof FormData)
    ) {
        headers.set('Content-Type', 'application/json');
    }

    const response = await fetch(`${API_BASE}${path}`, {
        ...init,
        headers,
        credentials: 'include',
    });

    if (!response.ok) {
        await throwResponseError(response);
    }

    if (response.status === 204) {
        return undefined as T;
    }

    return (await response.json()) as T;
}

export function extractDownloadFilename(disposition: string | null, fallback: string): string {
    if (!disposition) {
        return fallback;
    }

    const utf8Match = /filename\*=UTF-8''([^;]+)/i.exec(disposition);

    if (utf8Match?.[1]) {
        try {
            const decoded = decodeURIComponent(utf8Match[1].trim()).replace(/[/\\]/g, '');

            if (decoded !== '') {
                return decoded;
            }
        } catch {
            // Fall through to standard filename parameter parsing.
        }
    }

    const plainMatch = /filename=(?:"([^"]+)"|([^;]+))/i.exec(disposition);
    const candidate = (plainMatch?.[1] ?? plainMatch?.[2] ?? '').trim().replace(/[/\\]/g, '');

    return candidate !== '' ? candidate : fallback;
}

export async function apiDownload(path: string, fallbackFilename: string): Promise<string> {
    const headers = buildHeaders(undefined, 'application/octet-stream, application/json');

    const response = await fetch(`${API_BASE}${path}`, {
        method: 'GET',
        headers,
        credentials: 'include',
    });

    if (!response.ok) {
        await throwResponseError(response);
    }

    const filename = extractDownloadFilename(
        response.headers.get('Content-Disposition'),
        fallbackFilename
    );
    const blob = await response.blob();
    const objectUrl = URL.createObjectURL(blob);

    try {
        const anchor = document.createElement('a');
        anchor.href = objectUrl;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
    } finally {
        URL.revokeObjectURL(objectUrl);
    }

    return filename;
}
