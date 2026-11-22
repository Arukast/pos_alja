import type { Paginated } from '../api/types';

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
