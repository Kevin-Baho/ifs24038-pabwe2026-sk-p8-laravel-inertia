import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { CloseIcon } from '../UI/Icons';

/** Menampilkan flash `success` / `error` dari server sebagai toast yang hilang otomatis. */
export default function ToastHost() {
    const page = usePage();
    const [toasts, setToasts] = useState([]);
    const lastPage = useRef(null);
    const seq = useRef(0);

    useEffect(() => {
        if (lastPage.current === page) return;
        lastPage.current = page;

        const flash = page.props.flash || {};
        const incoming = [];
        if (flash.success) incoming.push({ type: 'success', text: flash.success });
        if (flash.error) incoming.push({ type: 'error', text: flash.error });
        if (!incoming.length) return;

        const stamped = incoming.map((t) => ({ ...t, id: ++seq.current }));
        setToasts((prev) => [...prev, ...stamped]);
        stamped.forEach((t) => setTimeout(() => setToasts((prev) => prev.filter((x) => x.id !== t.id)), 5000));
    }, [page]);

    if (!toasts.length) return null;

    return (
        <div className="fixed top-4 right-4 z-[80] flex flex-col gap-2 w-[min(24rem,calc(100vw-2rem))]">
            {toasts.map((t) => (
                <div
                    key={t.id}
                    role={t.type === 'error' ? 'alert' : 'status'}
                    className={`animate-toast-in flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg ${
                        t.type === 'error'
                            ? 'bg-rose-50 border-rose-200 text-rose-900'
                            : 'bg-emerald-50 border-emerald-200 text-emerald-900'
                    }`}
                >
                    <span className="flex-1">{t.text}</span>
                    <button
                        type="button"
                        aria-label="Tutup"
                        onClick={() => setToasts((prev) => prev.filter((x) => x.id !== t.id))}
                        className="opacity-60 hover:opacity-100"
                    >
                        <CloseIcon className="w-4 h-4" />
                    </button>
                </div>
            ))}
        </div>
    );
}
