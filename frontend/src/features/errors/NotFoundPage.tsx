import { Link } from 'react-router';

export function NotFoundPage() {
    return (
        <main className="bg-light-bg text-light-text dark:bg-surface-900 flex min-h-screen items-center justify-center px-4 dark:text-gray-100">
            <div className="text-center">
                <p className="text-brand-primary text-sm font-semibold uppercase tracking-[0.3em]">
                    404
                </p>
                <h1 className="mt-2 text-3xl font-bold">Halaman Tidak Ditemukan</h1>
                <p className="text-light-muted mt-2 text-sm dark:text-gray-400">
                    Halaman yang Anda minta tidak tersedia atau telah dipindahkan.
                </p>
                <Link
                    to="/"
                    className="bg-brand-primary hover:bg-brand-primary-hover mt-6 inline-flex rounded-lg px-4 py-2 text-sm font-semibold text-white"
                >
                    Kembali ke dasbor
                </Link>
            </div>
        </main>
    );
}
