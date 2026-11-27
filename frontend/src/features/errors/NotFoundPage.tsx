import { useNavigate } from 'react-router';

import { Button } from '../../components/ui/Button';

export function NotFoundPage() {
    const navigate = useNavigate();

    return (
        <main className="bg-light-bg text-light-text dark:bg-surface-900 flex min-h-screen items-center justify-center px-4 dark:text-gray-100">
            <section className="border-light-border bg-light-surface shadow-card dark:border-surface-300 dark:bg-surface-700 w-full max-w-md rounded-2xl border px-6 py-10 text-center dark:shadow-none">
                <img
                    src="/images/logo-alja.webp"
                    alt="Alja Digital Printing"
                    className="mx-auto h-auto w-40"
                />
                <p className="text-brand-fill dark:text-brand-primary-light mt-5 text-sm font-semibold uppercase tracking-[0.3em]">
                    404
                </p>
                <h1 className="text-light-text mt-2 text-3xl font-extrabold tracking-tight dark:text-gray-100">
                    Halaman Tidak Ditemukan
                </h1>
                <p className="text-light-muted mt-2 text-sm dark:text-gray-400">
                    Halaman yang Anda minta tidak ada.
                </p>
                <Button className="mt-6" onClick={() => navigate('/')}>
                    Kembali ke dasbor
                </Button>
            </section>
        </main>
    );
}
