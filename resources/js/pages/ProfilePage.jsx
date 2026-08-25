import ProfileForm from '../components/account/ProfileForm';
import EmailVerificationStatus from '../components/account/EmailVerificationStatus';
import { useAuth } from '../contexts/AuthContext';

export default function ProfilePage() {
    const { user } = useAuth();
    const isCustomer = user?.role === 'user';

    return (
        <div className="mx-auto max-w-3xl space-y-6">
            <div>
                <p className="text-sm font-medium tracking-wide text-amber-900/80 uppercase">Account</p>
                <h1 className="mt-1 text-3xl font-semibold text-stone-900">My Profile</h1>
                <p className="mt-2 text-sm text-stone-600">
                    Manage your personal details, security settings, and saved delivery information.
                </p>
            </div>
            <EmailVerificationStatus />
            <ProfileForm showAddress={isCustomer} showDeleteAccount={isCustomer} />
        </div>
    );
}
