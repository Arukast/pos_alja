import { useState } from 'react';
import { useNavigate } from 'react-router';

import { useAuth } from '../../auth/useAuth';
import { PageBanner } from '../../components/PageBanner';
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
            setNotice('Profil diperbarui.');
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
            setNotice('Kata sandi diperbarui.');
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
            <PageBanner
                tone="neutral"
                eyebrow="Akun"
                title="Profil & Pengaturan"
                description={
                    canEditProfile
                        ? 'Perbarui nama dan email Anda, ubah kata sandi, atau nonaktifkan akun Anda.'
                        : 'Lihat detail akun Anda dan ubah kata sandi.'
                }
            />

            {notice ? <Toast message={notice} onClose={() => setNotice('')} /> : null}

            <Card>
                <CardHeader>
                    <div>
                        <h2 className="font-semibold">Informasi profil</h2>
                        <p className="text-light-muted text-sm dark:text-gray-400">
                            Nama pengguna Anda tetap: {user.username}
                        </p>
                    </div>
                </CardHeader>
                <CardBody className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Nama lengkap"
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
                            Simpan profil
                        </Button>
                    ) : null}
                </CardBody>
            </Card>

            <Card>
                <CardHeader>
                    <h2 className="font-semibold">Ubah kata sandi</h2>
                </CardHeader>
                <CardBody className="space-y-4">
                    <Input
                        label="Kata sandi saat ini"
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
                            label="Kata sandi baru"
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
                            label="Konfirmasi kata sandi baru"
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
                        Perbarui kata sandi
                    </Button>
                </CardBody>
            </Card>

            {canEditProfile ? (
                <Card>
                    <CardHeader>
                        <div>
                            <h2 className="font-semibold text-red-600 dark:text-red-300">
                                Nonaktifkan akun
                            </h2>
                            <p className="text-light-muted text-sm dark:text-gray-400">
                                Akun akan dinonaktifkan dan dapat diaktifkan kembali oleh pemilik.
                            </p>
                        </div>
                    </CardHeader>
                    <CardBody>
                        <Button variant="danger" onClick={() => setDeleteOpen(true)}>
                            Nonaktifkan akun saya
                        </Button>
                    </CardBody>
                </Card>
            ) : null}

            <Modal
                open={deleteOpen}
                onClose={() => setDeleteOpen(false)}
                title="Nonaktifkan akun"
                description="Anda akan keluar dan akun Anda dinonaktifkan."
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setDeleteOpen(false)}>
                            Batal
                        </Button>
                        <Button variant="danger" onClick={() => void deleteAccount()}>
                            Nonaktifkan
                        </Button>
                    </>
                }
            >
                <div className="space-y-3">
                    <p className="text-light-muted text-sm dark:text-gray-400">
                        Konfirmasi dengan kata sandi Anda saat ini.
                    </p>
                    <Input
                        label="Kata Sandi"
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
