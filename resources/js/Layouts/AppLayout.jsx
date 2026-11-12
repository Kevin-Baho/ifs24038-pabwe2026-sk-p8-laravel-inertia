import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import Sidebar from '../Components/Layout/Sidebar';
import PublicNavbar from '../Components/Layout/PublicNavbar';
import EditorialFooter from '../Components/Layout/EditorialFooter';
import Brand from '../Components/Layout/Brand';
import LoadingIndicators from '../Components/Layout/LoadingIndicators';
import ToastHost from '../Components/Layout/ToastHost';

/**
 * Satu layout untuk ketiga tingkatan:
 *  - variant="public"    : Tingkat 1 — sticky top navbar + footer editorial, tanpa sidebar.
 *  - variant="dashboard" : Tingkat 2 (member) & 3 (admin) — sidebar.
 * Bila `variant` tidak diberikan, dipilih otomatis: login => dashboard, guest => public.
 */
export default function AppLayout({ children, variant }) {
    const { auth } = usePage().props;
    const [menuOpen, setMenuOpen] = useState(false);
    const mode = variant ?? (auth?.user ? 'dashboard' : 'public');

    return (
        <>
            <LoadingIndicators />
            <ToastHost />

            {mode === 'public' ? (
                <div className="min-h-screen bg-paper flex flex-col">
                    <PublicNavbar />
                    <main className="flex-1">{children}</main>
                    <EditorialFooter />
                </div>
            ) : (
                <div className="min-h-screen bg-slate-50 md:flex">
                    <div className="md:hidden sticky top-0 z-30 flex items-center justify-between bg-white border-b border-slate-200 px-4 h-14">
                        <Brand />
                        <button type="button" aria-label="Buka menu" onClick={() => setMenuOpen(true)} className="p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                    </div>
                    <Sidebar open={menuOpen} onClose={() => setMenuOpen(false)} />
                    <main className="flex-1 min-w-0 p-4 sm:p-6 lg:p-8">{children}</main>
                </div>
            )}
        </>
    );
}
