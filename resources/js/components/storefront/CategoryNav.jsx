import { NavLink } from 'react-router-dom';

export default function CategoryNav({ categories }) {
    if (!categories?.length) {
        return null;
    }

    const linkClass = ({ isActive }) =>
        [
            'whitespace-nowrap rounded-full px-3 py-1.5 text-sm transition',
            isActive ? 'bg-amber-800 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200 hover:bg-stone-100',
        ].join(' ');

    return (
        <nav className="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1" aria-label="Categories">
            <NavLink to="/menu" end className={linkClass}>
                All
            </NavLink>
            {categories.map((category) => (
                <NavLink
                    key={category.id}
                    to={`/menu/${category.slug}`}
                    className={linkClass}
                >
                    {category.name}
                </NavLink>
            ))}
        </nav>
    );
}
