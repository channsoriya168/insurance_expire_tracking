function daysUntil(dateString) {
    return Math.ceil((new Date(`${dateString}T00:00:00`) - new Date(new Date().toDateString())) / 86400000);
}

export function expiryStatus(dateString) {
    if (!dateString) {
        return { label: '—', accentClass: 'bg-slate-200', chipClass: 'bg-slate-50 text-slate-400', textClass: 'text-slate-400', isToday: false };
    }

    const days = daysUntil(dateString);
    const isToday = days === 0;

    if (days < 0) {
        return {
            label: `Expired ${Math.abs(days)} day${Math.abs(days) === 1 ? '' : 's'} ago · ${dateString}`,
            accentClass: 'bg-red-500',
            chipClass: 'bg-red-50 text-red-600',
            textClass: 'text-red-600',
            isToday,
        };
    }

    if (isToday) {
        return {
            label: `Expires today · ${dateString}`,
            accentClass: 'bg-red-500',
            chipClass: 'bg-red-50 text-red-600',
            textClass: 'text-red-600',
            isToday,
        };
    }

    if (days <= 10) {
        return {
            label: `Expires in ${days} day${days === 1 ? '' : 's'} · ${dateString}`,
            accentClass: 'bg-red-500',
            chipClass: 'bg-red-50 text-red-600',
            textClass: 'text-red-600',
            isToday,
        };
    }

    if (days <= 30) {
        return {
            label: `Expires in ${days} days · ${dateString}`,
            accentClass: 'bg-amber-500',
            chipClass: 'bg-amber-50 text-amber-600',
            textClass: 'text-amber-600',
            isToday,
        };
    }

    return {
        label: dateString,
        accentClass: 'bg-emerald-500',
        chipClass: 'bg-emerald-50 text-emerald-600',
        textClass: 'text-emerald-600',
        isToday,
    };
}
