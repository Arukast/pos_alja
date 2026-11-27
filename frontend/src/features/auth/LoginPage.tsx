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
        <main className="login-mesh relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
            <section className="w-full max-w-md overflow-hidden rounded-2xl border border-light-border bg-light-surface shadow-card dark:border-surface-300 dark:bg-surface-700 dark:shadow-none">
                <div className="brand-stripe-bar" />
                <div className="p-8">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <img
                            src="/images/logo-alja.webp"
                            alt=""
                            width={673}
                            height={371}
                            className="h-auto w-40"
                        />
                        <div>
                            <h1 className="text-2xl font-bold">Masuk</h1>
                        </div>
                    </div>

                    <p className="text-light-muted mt-3 text-sm dark:text-gray-400">
                        Gunakan nama pengguna dan kata sandi yang diberikan oleh manajer cabang Anda.
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
                </div>
            </section>
        </main>
    );
}
