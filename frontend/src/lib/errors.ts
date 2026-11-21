export interface ApiErrorPayload {
    message?: string;
    errors?: Record<string, string[]>;
}

export class ApiError extends Error {
    public readonly status: number;
    public readonly errors: Record<string, string[]>;
    public readonly payload: ApiErrorPayload;

    public constructor(status: number, payload: ApiErrorPayload = {}) {
        super(payload.message ?? `Permintaan gagal dengan status ${status}`);
        this.name = 'ApiError';
        this.status = status;
        this.errors = payload.errors ?? {};
        this.payload = payload;
    }
}

export function mapApiErrors(payload: unknown): Record<string, string> {
    if (!payload || typeof payload !== 'object') {
        return {};
    }

    const errors = (payload as ApiErrorPayload).errors;

    if (!errors || typeof errors !== 'object') {
        return {};
    }

    const mapped: Record<string, string> = {};

    for (const [field, messages] of Object.entries(errors)) {
        if (Array.isArray(messages) && messages.length > 0) {
            mapped[field] = String(messages[0]);
        }
    }

    return mapped;
}

export function errorMessage(error: unknown): string {
    if (error instanceof ApiError) {
        const first = Object.values(error.errors)[0]?.[0];

        return first ?? error.message;
    }

    if (error instanceof Error) {
        return error.message;
    }

    return 'Terjadi kesalahan.';
}
