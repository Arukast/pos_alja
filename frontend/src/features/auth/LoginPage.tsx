import { useState, type FormEvent } from 'react';
import { Navigate, useNavigate } from 'react-router';

import { useAuth } from '../../auth/useAuth';
import { roleHome } from '../../auth/roleHome';
import { Button } from '../../components/ui/Button';
import { Checkbox } from '../../components/ui/Checkbox';
import { Input } from '../../components/ui/Input';
import { errorMessage } from '../../lib/errors';

export function LoginPage() {
    const { user, login } = useAuth();
    const navigate = useNavigate();
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(false);
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);

    if (user) {
        return <Navigate to={roleHome(user.role)} replace />;
    }

    const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setError('');
        setSubmitting(true);

        try {
            const authenticated = await login(username, password, remember);
            navigate(roleHome(authenticated.role), { replace: true });
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <main className="bg-light-bg text-light-text dark:bg-surface-900 relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10 dark:text-gray-100">
            <div className="bg-brand-primary/20 absolute -top-24 -left-24 h-72 w-72 rounded-full blur-3xl" />
            <div className="bg-brand-secondary/20 absolute -right-24 -bottom-24 h-72 w-72 rounded-full blur-3xl" />

            <section className="border-light-border shadow-card relative z-10 w-full max-w-md rounded-3xl border bg-white p-8 dark:border-surface-300 dark:bg-surface-700">
                <div className="brand-stripe-bar mb-6" />
                <div className="flex items-center gap-3">
                    <span className="from-brand-primary to-brand-secondary h-10 w-10 rounded-2xl bg-gradient-to-br" />
                    <div>
                        <p className="text-brand-primary text-xs font-semibold uppercase tracking-[0.25em]">
                            Alja Digital Printing
                        </p>
                        <h1 className="text-2xl font-bold">Masuk</h1>
                    </div>
                </div>

                <p className="text-light-muted mt-3 text-sm dark:text-gray-400">
                    Gunakan nama pengguna dan kata sandi yang diberikan oleh administrator cabang Anda.
                </p>

                <form className="mt-6 space-y-4" onSubmit={(event) => void handleSubmit(event)}>
                    <Input
                        label="Nama Pengguna"
                        name="username"
                        autoComplete="username"
                        autoFocus
                        value={username}
                        onChange={(event) => setUsername(event.target.value)}
                        required
                    />
                    <Input
                        label="Kata Sandi"
                        name="password"
                        type="password"
                        autoComplete="current-password"
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        required
                    />
                    <Checkbox
                        label="Ingat Saya"
                        checked={remember}
                        onChange={(event) => setRemember(event.target.checked)}
                    />

                    {error ? (
                        <p
                            role="alert"
                            className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300"
                        >
                            {error}
                        </p>
                    ) : null}

                    <Button type="submit" className="w-full" loading={submitting}>
                        Masuk
                    </Button>
                </form>
            </section>
        </main>
    );
}
