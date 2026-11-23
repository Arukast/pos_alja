import { pageWindow } from '../../lib/pagination';
import { Button } from './Button';

export function Pagination({
    page,
    lastPage,
    total,
    perPage,
    onPageChange,
}: {
    page: number;
    lastPage: number;
    total?: number;
    perPage?: number;
    onPageChange: (page: number) => void;
}) {
    if (total === 0) {
        return null;
    }

    const range = total !== undefined && perPage !== undefined ? pageWindow(page, perPage, total) : null;

    if ((range && range.to === 0) || (total === undefined && lastPage <= 1)) {
        return null;
    }

    let summary = `Halaman ${page} dari ${lastPage}`;

    if (range && range.to > 0 && total !== undefined) {
        summary =
            range.from === range.to
                ? `Menampilkan ${range.from} dari ${total}`
                : `Menampilkan ${range.from}–${range.to} dari ${total}`;
    }

    return (
        <nav
            aria-label="Paginasi"
            className="flex flex-wrap items-center justify-between gap-3 pt-3 text-xs text-light-muted dark:text-gray-400"
        >
            <p>{summary}</p>
            <div className="flex items-center gap-2">
                <Button
                    variant="secondary"
                    size="sm"
                    disabled={page <= 1}
                    onClick={() => onPageChange(page - 1)}
                >
                    Sebelumnya
                </Button>
                <Button
                    variant="secondary"
                    size="sm"
                    disabled={page >= lastPage}
                    onClick={() => onPageChange(page + 1)}
                >
                    Berikutnya
                </Button>
            </div>
        </nav>
    );
}
