import { userInitials } from '../../lib/avatar';

export default function UserAvatar({ user, size = 'md', className = '' }) {
    const photoUrl = user?.profile_photo?.url ?? null;
    const initials = userInitials(user?.name);

    const sizeClass =
        size === 'lg'
            ? 'size-24 text-2xl'
            : size === 'sm'
              ? 'size-9 text-xs'
              : 'size-11 text-sm';

    if (photoUrl) {
        return (
            <img
                src={photoUrl}
                alt={user?.name ? `${user.name} profile photo` : 'Profile photo'}
                className={[
                    'shrink-0 rounded-full object-cover ring-2 ring-white/80',
                    sizeClass,
                    className,
                ]
                    .filter(Boolean)
                    .join(' ')}
            />
        );
    }

    return (
        <span
            className={[
                'inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-green-light to-brand-green font-semibold text-white ring-2 ring-white/80',
                sizeClass,
                className,
            ]
                .filter(Boolean)
                .join(' ')}
            aria-hidden={user?.name ? undefined : true}
        >
            {initials}
        </span>
    );
}
