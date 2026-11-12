import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import Spinner from '../UI/Spinner';

/**
 * Indikator loading global untuk setiap kunjungan Inertia (klik, submit form, ekspor via router, AI):
 *  1) Top progress bar, 2) floating pill "Memproses tindakan...".
 * Spinner pada tombol ditangani oleh <Button loading />.
 */
export default function LoadingIndicators() {
    const [active, setActive] = useState(false);

    useEffect(() => {
        const offStart = router.on('start', () => setActive(true));
        const offFinish = router.on('finish', () => setActive(false));
        return () => {
            offStart();
            offFinish();
        };
    }, []);

    if (!active) return null;

    return (
        <>
            <div className="fixed top-0 left-0 right-0 z-[90] h-1 overflow-hidden bg-primary-100" role="progressbar" aria-label="Memuat">
                <div className="h-full w-1/3 bg-primary-600 animate-bar-slide" />
            </div>
            <div
                role="status"
                className="fixed bottom-5 right-5 z-[90] flex items-center gap-2.5 rounded-full bg-slate-900 text-white pl-3.5 pr-4 py-2.5 text-sm font-medium shadow-xl animate-pill-in"
            >
                <Spinner className="w-4 h-4 text-primary-100" />
                <span>Memproses tindakan...</span>
            </div>
        </>
    );
}
