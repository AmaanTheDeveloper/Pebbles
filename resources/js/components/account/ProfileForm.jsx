import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    changePassword,
    deleteAccount,
    removeProfilePhoto,
    updateProfile,
    uploadProfilePhoto,
} from '../../api/profile';
import { useAuth } from '../../contexts/AuthContext';
import { firstFieldError, normalizeApiError } from '../../lib/errors';
import UserAvatar from './UserAvatar';

function Flash({ type, message, onDismiss }) {
    if (!message) {
        return null;
    }

    return (
        <div
            className={[
                'rounded-xl px-4 py-3 text-sm',
                type === 'success' ? 'bg-emerald-50 text-emerald-900 ring-1 ring-emerald-200' : 'bg-red-50 text-red-800 ring-1 ring-red-200',
            ].join(' ')}
        >
            <div className="flex items-start justify-between gap-3">
                <p>{message}</p>
                {onDismiss ? (
                    <button type="button" onClick={onDismiss} className="text-xs font-semibold uppercase">
                        Dismiss
                    </button>
                ) : null}
            </div>
        </div>
    );
}

export default function ProfileForm({ showAddress = true, showDeleteAccount = true }) {
    const { user, refreshUser, logout } = useAuth();
    const navigate = useNavigate();
    const fileInputRef = useRef(null);

    const [flash, setFlash] = useState(null);
    const [profileErrors, setProfileErrors] = useState({});
    const [passwordErrors, setPasswordErrors] = useState({});
    const [deleteErrors, setDeleteErrors] = useState({});
    const [savingProfile, setSavingProfile] = useState(false);
    const [uploadingPhoto, setUploadingPhoto] = useState(false);
    const [removingPhoto, setRemovingPhoto] = useState(false);
    const [savingPassword, setSavingPassword] = useState(false);
    const [deletingAccount, setDeletingAccount] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deletePassword, setDeletePassword] = useState('');

    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [addressLine1, setAddressLine1] = useState('');
    const [addressLine2, setAddressLine2] = useState('');
    const [addressArea, setAddressArea] = useState('');
    const [addressCity, setAddressCity] = useState('');
    const [currentPassword, setCurrentPassword] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [photoPreview, setPhotoPreview] = useState(null);

    useEffect(() => {
        if (!user) {
            return;
        }

        setName(user.name ?? '');
        setEmail(user.email ?? '');
        setPhone(user.phone ?? '');
        setAddressLine1(user.default_address?.line1 ?? '');
        setAddressLine2(user.default_address?.line2 ?? '');
        setAddressArea(user.default_address?.area ?? '');
        setAddressCity(user.default_address?.city ?? 'Karachi');
    }, [user]);

    useEffect(() => {
        return () => {
            if (photoPreview) {
                URL.revokeObjectURL(photoPreview);
            }
        };
    }, [photoPreview]);

    const previewUser = useMemo(
        () => ({
            ...user,
            profile_photo: photoPreview ? { url: photoPreview } : user?.profile_photo,
        }),
        [user, photoPreview],
    );

    if (!user) {
        return null;
    }

    const handleProfileSave = async (event) => {
        event.preventDefault();
        setSavingProfile(true);
        setProfileErrors({});
        setFlash(null);

        try {
            const payload = {
                name,
                email,
                phone: phone || null,
            };

            if (showAddress) {
                payload.address = {
                    recipient_name: name,
                    phone: phone || null,
                    line1: addressLine1 || null,
                    line2: addressLine2 || null,
                    area: addressArea || null,
                    city: addressCity || null,
                };
            }

            const response = await updateProfile(payload);
            await refreshUser();
            setFlash({ type: 'success', message: response.message ?? 'Profile updated successfully.' });
        } catch (error) {
            const normalized = normalizeApiError(error);
            setProfileErrors(normalized.errors ?? {});
            setFlash({ type: 'error', message: normalized.message });
        } finally {
            setSavingProfile(false);
        }
    };

    const handlePhotoSelect = async (event) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        setUploadingPhoto(true);
        setFlash(null);

        if (photoPreview) {
            URL.revokeObjectURL(photoPreview);
        }
        setPhotoPreview(URL.createObjectURL(file));

        try {
            const response = await uploadProfilePhoto(file);
            await refreshUser();
            setPhotoPreview(null);
            setFlash({ type: 'success', message: response.message ?? 'Profile photo updated successfully.' });
        } catch (error) {
            setPhotoPreview(null);
            const normalized = normalizeApiError(error);
            setFlash({ type: 'error', message: normalized.message });
        } finally {
            setUploadingPhoto(false);
            event.target.value = '';
        }
    };

    const handleRemovePhoto = async () => {
        setRemovingPhoto(true);
        setFlash(null);

        try {
            const response = await removeProfilePhoto();
            await refreshUser();
            setPhotoPreview(null);
            setFlash({ type: 'success', message: response.message ?? 'Profile photo removed successfully.' });
        } catch (error) {
            const normalized = normalizeApiError(error);
            setFlash({ type: 'error', message: normalized.message });
        } finally {
            setRemovingPhoto(false);
        }
    };

    const handlePasswordSave = async (event) => {
        event.preventDefault();
        setSavingPassword(true);
        setPasswordErrors({});
        setFlash(null);

        try {
            const response = await changePassword({
                current_password: currentPassword,
                password,
                password_confirmation: passwordConfirmation,
            });
            await refreshUser();
            setCurrentPassword('');
            setPassword('');
            setPasswordConfirmation('');
            setFlash({ type: 'success', message: response.message ?? 'Password changed successfully.' });
        } catch (error) {
            const normalized = normalizeApiError(error);
            setPasswordErrors(normalized.errors ?? {});
            setFlash({ type: 'error', message: normalized.message });
        } finally {
            setSavingPassword(false);
        }
    };

    const handleDeleteAccount = async () => {
        setDeletingAccount(true);
        setDeleteErrors({});
        setFlash(null);

        try {
            const response = await deleteAccount({ password: deletePassword });
            await logout();
            navigate('/', { replace: true, state: { flash: response.message ?? 'Account deleted successfully.' } });
        } catch (error) {
            const normalized = normalizeApiError(error);
            setDeleteErrors(normalized.errors ?? {});
            setFlash({ type: 'error', message: normalized.message });
        } finally {
            setDeletingAccount(false);
        }
    };

    return (
        <div className="space-y-6">
            <Flash type={flash?.type} message={flash?.message} onDismiss={() => setFlash(null)} />

            <section className="rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                <div className="flex flex-col items-center gap-4 text-center sm:flex-row sm:text-left">
                    <UserAvatar user={previewUser} size="lg" />
                    <div className="space-y-1">
                        <h2 className="text-xl font-semibold text-stone-900">{user.name}</h2>
                        <p className="text-sm text-stone-600">{user.email}</p>
                        <p className="text-sm text-stone-500">
                            {user.role_label}
                            {user.access_label ? ` · ${user.access_label}` : ''}
                        </p>
                    </div>
                </div>
                <div className="mt-5 flex flex-wrap justify-center gap-2 sm:justify-start">
                    <button
                        type="button"
                        className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                        disabled={uploadingPhoto}
                        onClick={() => fileInputRef.current?.click()}
                    >
                        {uploadingPhoto ? 'Uploading…' : 'Change photo'}
                    </button>
                    {user.profile_photo?.url ? (
                        <button
                            type="button"
                            className="rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 ring-1 ring-stone-300 hover:bg-stone-100 disabled:opacity-60"
                            disabled={removingPhoto}
                            onClick={handleRemovePhoto}
                        >
                            {removingPhoto ? 'Removing…' : 'Remove photo'}
                        </button>
                    ) : null}
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        className="hidden"
                        onChange={handlePhotoSelect}
                    />
                </div>
            </section>

            <form onSubmit={handleProfileSave} className="space-y-6 rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                <div>
                    <h3 className="text-lg font-semibold text-stone-900">Personal information</h3>
                    <p className="mt-1 text-sm text-stone-600">Update your contact details used across Pebbles.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <label className="block space-y-1 text-sm sm:col-span-2">
                        <span className="font-medium text-stone-700">Full name</span>
                        <input
                            value={name}
                            onChange={(event) => setName(event.target.value)}
                            className="w-full rounded-lg border border-stone-300 px-3 py-2"
                            required
                        />
                        {firstFieldError(profileErrors, 'name') ? (
                            <span className="text-xs text-red-600">{firstFieldError(profileErrors, 'name')}</span>
                        ) : null}
                    </label>
                    <label className="block space-y-1 text-sm sm:col-span-2">
                        <span className="font-medium text-stone-700">Email</span>
                        <input
                            type="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            className="w-full rounded-lg border border-stone-300 px-3 py-2"
                            required
                        />
                        {firstFieldError(profileErrors, 'email') ? (
                            <span className="text-xs text-red-600">{firstFieldError(profileErrors, 'email')}</span>
                        ) : null}
                    </label>
                    <label className="block space-y-1 text-sm sm:col-span-2">
                        <span className="font-medium text-stone-700">Phone</span>
                        <input
                            value={phone}
                            onChange={(event) => setPhone(event.target.value)}
                            className="w-full rounded-lg border border-stone-300 px-3 py-2"
                        />
                        {firstFieldError(profileErrors, 'phone') ? (
                            <span className="text-xs text-red-600">{firstFieldError(profileErrors, 'phone')}</span>
                        ) : null}
                    </label>
                </div>

                {showAddress ? (
                    <div className="space-y-4 border-t border-stone-100 pt-6">
                        <div>
                            <h4 className="text-base font-semibold text-stone-900">Saved delivery information</h4>
                            <p className="mt-1 text-sm text-stone-600">
                                Used to pre-fill checkout for your next order.
                            </p>
                        </div>
                        <label className="block space-y-1 text-sm">
                            <span className="font-medium text-stone-700">Address</span>
                            <input
                                value={addressLine1}
                                onChange={(event) => setAddressLine1(event.target.value)}
                                className="w-full rounded-lg border border-stone-300 px-3 py-2"
                            />
                        </label>
                        <label className="block space-y-1 text-sm">
                            <span className="font-medium text-stone-700">Address line 2</span>
                            <input
                                value={addressLine2}
                                onChange={(event) => setAddressLine2(event.target.value)}
                                className="w-full rounded-lg border border-stone-300 px-3 py-2"
                            />
                        </label>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <label className="block space-y-1 text-sm">
                                <span className="font-medium text-stone-700">Area / locality</span>
                                <input
                                    value={addressArea}
                                    onChange={(event) => setAddressArea(event.target.value)}
                                    className="w-full rounded-lg border border-stone-300 px-3 py-2"
                                />
                            </label>
                            <label className="block space-y-1 text-sm">
                                <span className="font-medium text-stone-700">City</span>
                                <input
                                    value={addressCity}
                                    onChange={(event) => setAddressCity(event.target.value)}
                                    className="w-full rounded-lg border border-stone-300 px-3 py-2"
                                />
                            </label>
                        </div>
                    </div>
                ) : null}

                <button
                    type="submit"
                    disabled={savingProfile}
                    className="rounded-lg bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                >
                    {savingProfile ? 'Saving…' : 'Save profile'}
                </button>
            </form>

            <form onSubmit={handlePasswordSave} className="space-y-4 rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                <div>
                    <h3 className="text-lg font-semibold text-stone-900">Change password</h3>
                    <p className="mt-1 text-sm text-stone-600">Use a strong password you do not use elsewhere.</p>
                </div>
                <label className="block space-y-1 text-sm">
                    <span className="font-medium text-stone-700">Current password</span>
                    <input
                        type="password"
                        value={currentPassword}
                        onChange={(event) => setCurrentPassword(event.target.value)}
                        className="w-full rounded-lg border border-stone-300 px-3 py-2"
                        required
                    />
                    {firstFieldError(passwordErrors, 'current_password') ? (
                        <span className="text-xs text-red-600">{firstFieldError(passwordErrors, 'current_password')}</span>
                    ) : null}
                </label>
                <label className="block space-y-1 text-sm">
                    <span className="font-medium text-stone-700">New password</span>
                    <input
                        type="password"
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        className="w-full rounded-lg border border-stone-300 px-3 py-2"
                        required
                    />
                </label>
                <label className="block space-y-1 text-sm">
                    <span className="font-medium text-stone-700">Confirm new password</span>
                    <input
                        type="password"
                        value={passwordConfirmation}
                        onChange={(event) => setPasswordConfirmation(event.target.value)}
                        className="w-full rounded-lg border border-stone-300 px-3 py-2"
                        required
                    />
                    {firstFieldError(passwordErrors, 'password') ? (
                        <span className="text-xs text-red-600">{firstFieldError(passwordErrors, 'password')}</span>
                    ) : null}
                </label>
                <button
                    type="submit"
                    disabled={savingPassword}
                    className="rounded-lg bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                >
                    {savingPassword ? 'Updating…' : 'Update password'}
                </button>
            </form>

            {showDeleteAccount ? (
                <section className="rounded-2xl border border-red-200 bg-red-50/40 p-6">
                    <h3 className="text-lg font-semibold text-red-900">Delete account</h3>
                    <p className="mt-1 text-sm text-red-800">
                        This permanently removes your account access. Your order history remains for business records.
                    </p>
                    <button
                        type="button"
                        onClick={() => setDeleteOpen(true)}
                        className="mt-4 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800"
                    >
                        Delete account
                    </button>
                </section>
            ) : null}

            {deleteOpen ? (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/50 p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <h4 className="text-lg font-semibold text-stone-900">Delete your account?</h4>
                        <p className="mt-2 text-sm text-stone-600">
                            This action cannot be undone. Confirm with your current password.
                        </p>
                        <label className="mt-4 block space-y-1 text-sm">
                            <span className="font-medium text-stone-700">Current password</span>
                            <input
                                type="password"
                                value={deletePassword}
                                onChange={(event) => setDeletePassword(event.target.value)}
                                className="w-full rounded-lg border border-stone-300 px-3 py-2"
                            />
                            {firstFieldError(deleteErrors, 'password') ? (
                                <span className="text-xs text-red-600">{firstFieldError(deleteErrors, 'password')}</span>
                            ) : null}
                        </label>
                        <div className="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                className="rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 ring-1 ring-stone-300 hover:bg-stone-100"
                                onClick={() => {
                                    setDeleteOpen(false);
                                    setDeletePassword('');
                                }}
                            >
                                Keep my account
                            </button>
                            <button
                                type="button"
                                disabled={deletingAccount || !deletePassword}
                                onClick={handleDeleteAccount}
                                className="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-60"
                            >
                                {deletingAccount ? 'Deleting…' : 'Delete account'}
                            </button>
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
