import { useState } from 'react';
import { useNavigate } from 'react-router';

import { useAuth } from '../../auth/useAuth';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Toast } from '../../components/ui/Toast';
import { apiFetch } from '../../lib/api';
import { ApiError, errorMessage, mapApiErrors } from '../../lib/errors';
import type { AuthUser } from '../../auth/types';

export function ProfilePage() {
    const { user, refresh } = useAuth();
    const navigate = useNavigate();
    const [profile, setProfile] = useState({ name: user?.name ?? '', email: user?.email ?? '' });
    const [profileErrors, setProfileErrors] = useState<Record<string, string>>({});
    const [profileLoading, setProfileLoading] = useState(false);
    const [password, setPassword] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const [passwordErrors, setPasswordErrors] = useState<Record<string, string>>({});
    const [passwordLoading, setPasswordLoading] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deletePassword, setDeletePassword] = useState('');
    const [deleteError, setDeleteError] = useState('');
    const [notice, setNotice] = useState('');

    const canEditProfile = user ? ['owner', 'manager'].includes(user.role) : false;

    if (!user) {
        return null;
    }

    const saveProfile = async () => {
        setProfileLoading(true);
        setProfileErrors({});

        try {
            const response = await apiFetch<{ user: AuthUser }>('/auth/profile', {
                method: 'PUT',
                body: JSON.stringify(profile),
            });

            await refresh();
            setProfile({ name: response.user.name, email: response.user.email ?? '' });
            setNotice('Profile updated.');
        } catch (caught) {
            setProfileErrors(caught instanceof ApiError ? mapApiErrors(caught.payload) : {});
            setNotice(errorMessage(caught));
        } finally {
            setProfileLoading(false);
        }
    };

    const savePassword = async () => {
        setPasswordLoading(true);
        setPasswordErrors({});

        try {
            await apiFetch<void>('/auth/password', {
                method: 'PUT',
                body: JSON.stringify(password),
            });

            setPassword({ current_password: '', password: '', password_confirmation: '' });
            setNotice('Password updated.');
        } catch (caught) {
            setPasswordErrors(caught instanceof ApiError ? mapApiErrors(caught.payload) : {});
            setNotice(errorMessage(caught));
        } finally {
            setPasswordLoading(false);
        }
    };

    const deleteAccount = async () => {
        setDeleteError('');

        try {
            await apiFetch<void>('/auth/profile', {
                method: 'DELETE',
                body: JSON.stringify({ password: deletePassword }),
            });

            await refresh();
            navigate('/login', { replace: true });
        } catch (caught) {
            setDeleteError(errorMessage(caught));
        }
    };

    return (
        <div className="space-y-6">
            <header>
                <p className="text-brand-primary text-xs font-semibold uppercase tracking-[0.25em]">
                    Account
                </p>
                <h1 className="mt-1 text-2xl font-bold">Profile & Settings</h1>
                <p className="text-light-muted mt-1 text-sm dark:text-gray-400">
                    {canEditProfile
                        ? 'Update your name and email, change your password, or deactivate your account.'
                        : 'View your account details and change your password.'}
                </p>
            </header>

            {notice ? <Toast message={notice} onClose={() => setNotice('')} /> : null}

            <Card>
                <CardHeader>
                    <div>
                        <h2 className="font-semibold">Profile information</h2>
                        <p className="text-light-muted text-sm dark:text-gray-400">
                            Your username is fixed: {user.username}
                        </p>
                    </div>
                </CardHeader>
                <CardBody className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Full name"
                            value={profile.name}
                            disabled={!canEditProfile}
                            onChange={(event) =>
                                setProfile((current) => ({ ...current, name: event.target.value }))
                            }
                            error={profileErrors.name}
                        />
                        <Input
                            label="Email"
                            type="email"
                            value={profile.email}
                            disabled={!canEditProfile}
                            onChange={(event) =>
                                setProfile((current) => ({ ...current, email: event.target.value }))
                            }
                            error={profileErrors.email}
                        />
                    </div>
                    {canEditProfile ? (
                        <Button loading={profileLoading} onClick={() => void saveProfile()}>
                            Save profile
                        </Button>
                    ) : null}
                </CardBody>
            </Card>

            <Card>
                <CardHeader>
                    <h2 className="font-semibold">Change password</h2>
                </CardHeader>
                <CardBody className="space-y-4">
                    <Input
                        label="Current password"
                        type="password"
                        autoComplete="current-password"
                        value={password.current_password}
                        onChange={(event) =>
                            setPassword((current) => ({
                                ...current,
                                current_password: event.target.value,
                            }))
                        }
                        error={passwordErrors.current_password}
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="New password"
                            type="password"
                            autoComplete="new-password"
                            value={password.password}
                            onChange={(event) =>
                                setPassword((current) => ({
                                    ...current,
                                    password: event.target.value,
                                }))
                            }
                            error={passwordErrors.password}
                        />
                        <Input
                            label="Confirm new password"
                            type="password"
                            autoComplete="new-password"
                            value={password.password_confirmation}
                            onChange={(event) =>
                                setPassword((current) => ({
                                    ...current,
                                    password_confirmation: event.target.value,
                                }))
                            }
                        />
                    </div>
                    <Button loading={passwordLoading} onClick={() => void savePassword()}>
                        Update password
                    </Button>
                </CardBody>
            </Card>

            {canEditProfile ? (
                <Card>
                    <CardHeader>
                        <div>
                            <h2 className="font-semibold text-red-600 dark:text-red-300">
                                Deactivate account
                            </h2>
                            <p className="text-light-muted text-sm dark:text-gray-400">
                                The account is soft-deleted and can be restored by an owner.
                            </p>
                        </div>
                    </CardHeader>
                    <CardBody>
                        <Button variant="danger" onClick={() => setDeleteOpen(true)}>
                            Deactivate my account
                        </Button>
                    </CardBody>
                </Card>
            ) : null}

            <Modal
                open={deleteOpen}
                onClose={() => setDeleteOpen(false)}
                title="Deactivate account"
                description="This signs you out and soft-deletes your account."
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setDeleteOpen(false)}>
                            Cancel
                        </Button>
                        <Button variant="danger" onClick={() => void deleteAccount()}>
                            Deactivate
                        </Button>
                    </>
                }
            >
                <div className="space-y-3">
                    <p className="text-light-muted text-sm dark:text-gray-400">
                        Confirm with your current password.
                    </p>
                    <Input
                        label="Password"
                        type="password"
                        autoComplete="current-password"
                        value={deletePassword}
                        onChange={(event) => setDeletePassword(event.target.value)}
                        error={deleteError}
                    />
                </div>
            </Modal>
        </div>
    );
}
