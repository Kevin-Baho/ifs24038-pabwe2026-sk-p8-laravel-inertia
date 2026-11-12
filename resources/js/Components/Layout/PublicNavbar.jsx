import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Brand from './Brand';
import Avatar from './Avatar';

const links = [
    { href: '/', label: 'Beranda', match: (u) => u === '/' },
    { href: '/articles', label: 'Artikel', match: (u) => u.startsWith('/articles') },
];

/** Tingkat 1: Sticky Top Navbar untuk publik/guest (tanpa sidebar). */
export default function PublicNavbar() {
    const { auth } = usePage().props;
    const { url } = usePage();
    const [open, setOpen] = useState(false);
    const user = auth?.user;
    const dashboardHref = user?.role === 'admin' ? '/admin/users' : '/dashboard/posts';

    return (
        <header className="sticky top-0 z-40 border-b border-slate-200/80 bg-paper/90 backdrop-blur">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 h-16 flex items-center gap-6">
                <Brand />

                <nav className="hidden md:flex items-center gap-1 ml-2" aria-label="Navigasi utama">
                    {links.map((l) => (
                        <Link
                            key={l.href}
                            href={l.href}
                            className={`px-3 py-2 rounded-lg text-sm font-medium transition ${
                                l.match(url) ? 'text-primary-700 bg-primary-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                            }`}
                        >
                            {l.label}
                        </Link>
                    ))}
                </nav>

                <div className="ml-auto hidden md:flex items-center gap-2">
                    {user ? (
                        <>
                            <Link href={dashboardHref} className="inline-flex items-center gap-2.5 pl-1.5 pr-4 py-1.5 rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-800 hover:border-primary-500 transition">
                                <Avatar name={user.name} url={user.avatar_url} path={user.profile_photo_path} className="w-7 h-7 text-xs" />
                                {user.role === 'admin' ? 'Panel Admin' : 'Dashboard Saya'}
                            </Link>
                        </>
                    ) : (
                        <>
                            <Link href="/login" className="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 transition">
                                Masuk
                            </Link>
                            <Link href="/register" className="px-4 py-2 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition shadow-sm">
                                Daftar gratis
                            </Link>
                        </>
                    )}
                </div>

                <button
                    type="button"
                    className="md:hidden ml-auto p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                    aria-label="Buka menu"
                    aria-expanded={open}
                    onClick={() => setOpen((v) => !v)}
                >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        {open ? <path strokeLinecap="round" d="M6 18L18 6M6 6l12 12" /> : <path strokeLinecap="round" d="M4 6h16M4 12h16M4 18h16" />}
                    </svg>
                </button>
            </div>

            {open && (
                <div className="md:hidden border-t border-slate-200 bg-paper px-4 py-3 space-y-1">
                    {links.map((l) => (
                        <Link key={l.href} href={l.href} onClick={() => setOpen(false)} className="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">
                            {l.label}
                        </Link>
                    ))}
                    <div className="pt-2 flex gap-2">
                        {user ? (
                            <Link href={dashboardHref} className="flex-1 text-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white">
                                {user.role === 'admin' ? 'Panel Admin' : 'Dashboard Saya'}
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="flex-1 text-center px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-300 text-slate-700">Masuk</Link>
                                <Link href="/register" className="flex-1 text-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white">Daftar</Link>
                            </>
                        )}
                    </div>
                </div>
            )}
        </header>
    );
}
