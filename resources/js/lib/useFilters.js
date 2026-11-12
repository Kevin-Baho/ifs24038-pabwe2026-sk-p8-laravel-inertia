import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';

/**
 * Sinkronkan filter (search/status/category) ke query string dengan debounce.
 * `url` adalah path halaman yang sedang aktif.
 */
export default function useFilters(url, initial, delay = 350) {
    const [values, setValues] = useState(initial);
    const first = useRef(true);

    useEffect(() => {
        if (first.current) {
            first.current = false;
            return;
        }
        const timer = setTimeout(() => {
            const query = Object.fromEntries(Object.entries(values).filter(([, v]) => v !== '' && v != null));
            router.get(url, query, { preserveState: true, preserveScroll: true, replace: true });
        }, delay);
        return () => clearTimeout(timer);
    }, [values]);

    const set = (key, value) => setValues((v) => ({ ...v, [key]: value }));

    return [values, set];
}
