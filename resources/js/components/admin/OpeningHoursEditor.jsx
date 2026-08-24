import { AdminFormField, AdminTextareaField } from '../../components/admin/AdminFormField';
import { WEEKDAYS } from '../../lib/contactHelpers';

const dayLabels = {
    monday: 'Monday',
    tuesday: 'Tuesday',
    wednesday: 'Wednesday',
    thursday: 'Thursday',
    friday: 'Friday',
    saturday: 'Saturday',
    sunday: 'Sunday',
};

export default function OpeningHoursEditor({ value, onChange, errors = {} }) {
    const updateDay = (day, patch) => {
        onChange({
            ...value,
            [day]: {
                ...value[day],
                ...patch,
            },
        });
    };

    return (
        <div className="space-y-3">
            {WEEKDAYS.map((day) => (
                <div
                    key={day}
                    className="grid gap-3 rounded-lg border border-stone-200 p-3 sm:grid-cols-[8rem_1fr_1fr_1fr]"
                >
                    <p className="text-sm font-semibold text-stone-800">{dayLabels[day]}</p>
                    <label className="flex items-center gap-2 text-sm text-stone-700">
                        <input
                            type="checkbox"
                            checked={Boolean(value[day]?.closed)}
                            onChange={(event) => updateDay(day, { closed: event.target.checked })}
                        />
                        Closed
                    </label>
                    <AdminFormField
                        label="Opens"
                        name={`opening_hours.${day}.opens`}
                        type="time"
                        value={value[day]?.opens ?? ''}
                        onChange={(event) => updateDay(day, { opens: event.target.value })}
                        disabled={Boolean(value[day]?.closed)}
                        errors={errors}
                    />
                    <AdminFormField
                        label="Closes"
                        name={`opening_hours.${day}.closes`}
                        type="time"
                        value={value[day]?.closes ?? ''}
                        onChange={(event) => updateDay(day, { closes: event.target.value })}
                        disabled={Boolean(value[day]?.closed)}
                        errors={errors}
                    />
                    <div className="sm:col-span-4">
                        <AdminTextareaField
                            label="Special note (optional)"
                            name={`opening_hours.${day}.note`}
                            rows={2}
                            value={value[day]?.note ?? ''}
                            onChange={(event) => updateDay(day, { note: event.target.value })}
                            hint="Holiday closure or special hours message for this day."
                            errors={errors}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}
