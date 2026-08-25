import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import SeoHead from '../components/seo/SeoHead';
import { readOrderType, writeOrderType } from '../lib/orderTypeStorage';

const options = [
    {
        value: 'delivery',
        title: 'Delivery',
        description: 'Get your order brought to your door when delivery zones are available.',
        icon: '🛵',
    },
    {
        value: 'takeaway',
        title: 'Takeaway',
        description: 'Place your order now and pick it up from your selected branch.',
        icon: '🥡',
    },
    {
        value: 'dine_in',
        title: 'Dine-In',
        description: 'Order from your table and we will bring it out when ready.',
        icon: '🍽️',
    },
];

export default function OrderOnlinePage() {
    const navigate = useNavigate();
    const [selected, setSelected] = useState(() => readOrderType());

    const handleContinue = () => {
        if (selected !== 'delivery' && selected !== 'takeaway' && selected !== 'dine_in') {
            return;
        }

        writeOrderType(selected);
        navigate('/menu');
    };

    return (
        <div className="mx-auto max-w-2xl space-y-8 py-4 sm:py-8">
            <SeoHead
                title="Order online"
                description="Order Pebbles ice cream, shakes, faluda, and kulfi for delivery, takeaway, or dine-in."
                path="/order"
            />
            <div className="space-y-2 text-center sm:text-left">
                <p className="text-xs font-semibold tracking-widest text-brand-orange uppercase">Order online</p>
                <h1 className="font-display text-3xl font-semibold tracking-tight text-brand-ink sm:text-4xl">
                    How would you like to receive your order?
                </h1>
                <p className="text-sm text-brand-muted sm:text-base">
                    Choose delivery, takeaway, or dine-in, then browse shakes, ice cream, Limca, and more.
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-3" role="radiogroup" aria-label="Order type">
                {options.map((option) => {
                    const isActive = selected === option.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            role="radio"
                            aria-checked={isActive}
                            onClick={() => setSelected(option.value)}
                            className={[
                                'rounded-3xl p-6 text-left transition duration-200',
                                isActive
                                    ? 'bg-brand-green text-white shadow-lg shadow-brand-green/25 ring-2 ring-brand-green'
                                    : 'bg-white text-brand-ink ring-1 ring-brand-green/15 hover:-translate-y-0.5 hover:shadow-md hover:ring-brand-orange/40',
                            ].join(' ')}
                        >
                            <span className="text-3xl" aria-hidden="true">
                                {option.icon}
                            </span>
                            <p className="mt-4 font-display text-2xl font-semibold">{option.title}</p>
                            <p className={`mt-2 text-sm leading-relaxed ${isActive ? 'text-white/85' : 'text-brand-muted'}`}>
                                {option.description}
                            </p>
                        </button>
                    );
                })}
            </div>

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    disabled={!selected}
                    onClick={handleContinue}
                    className="btn-orange btn-shine px-6 py-3 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Continue to menu
                    <span className="btn-arrow" aria-hidden="true">
                        →
                    </span>
                </button>
                <Link to="/menu" className="text-sm font-semibold text-brand-green hover:text-brand-orange">
                    Skip and browse menu
                </Link>
            </div>
        </div>
    );
}
