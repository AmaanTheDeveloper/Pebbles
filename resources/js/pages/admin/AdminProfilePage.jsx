import ProfileForm from '../../components/account/ProfileForm';
import { AdminPageHeader } from '../../layouts/AdminLayout';

export default function AdminProfilePage() {
    return (
        <div className="mx-auto max-w-3xl">
            <AdminPageHeader
                title="My Profile"
                description="Manage your personal account details and password."
            />
            <ProfileForm showAddress={false} showDeleteAccount={false} />
        </div>
    );
}
