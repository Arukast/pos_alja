import { Button } from './Button';

export function Pagination({
    page,
    lastPage,
    onPageChange,
}: {
    page: number;
    lastPage: number;
    onPageChange: (page: number) => void;
}) {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <nav
            aria-label="Pagination"
            className="flex flex-wrap items-center justify-between gap-3 pt-3"
        >
            <p className="text-light-muted text-sm dark:text-gray-400">
                Page {page} of {lastPage}
            </p>
            <div className="flex items-center gap-2">
                <Button
                    variant="secondary"
                    size="sm"
                    disabled={page <= 1}
                    onClick={() => onPageChange(page - 1)}
                >
                    Previous
                </Button>
                <Button
                    variant="secondary"
                    size="sm"
                    disabled={page >= lastPage}
                    onClick={() => onPageChange(page + 1)}
                >
                    Next
                </Button>
            </div>
        </nav>
    );
}
