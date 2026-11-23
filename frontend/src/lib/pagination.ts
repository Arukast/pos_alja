import type { Paginated } from '../api/types';

export function pageWindow(page: number, perPage: number, total: number): { from: number; to: number } {
    const from = (page - 1) * perPage + 1;

    if (total <= 0 || page < 1 || perPage < 1 || from > total) {
        return { from: 0, to: 0 };
    }

    return { from, to: Math.min(page * perPage, total) };
}

export function collectionAsPage<T>(data: T[]): Paginated<T> {
    return {
        data,
        links: { first: null, last: null, prev: null, next: null },
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: data.length,
            total: data.length,
        },
    };
}
