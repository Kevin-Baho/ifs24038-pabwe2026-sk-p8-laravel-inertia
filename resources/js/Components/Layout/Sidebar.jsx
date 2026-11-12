import { Link, router, usePage } from '@inertiajs/react';
import Brand from './Brand';
import Avatar from './Avatar';

const Icon = ({ d }) => (
    <svg className="w-5 h-5 shrink-0" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24"><path d={d} /></svg>
);

const ICONS = {
    posts: 'M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5zM14 3v5h5M9 13h6M9 17h6',
    globe: 'M12 21a9 9 0 100-18 9 9 0 000 18zM3 12h18M12 3a14 14 0 010 18M12 3a14 14 0 000 18',
    user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    users: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.870M16 3.130a4 4 0 010 7.750',
    logout: 'M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9',
};

function navFor(role) {
    if (role === 'admin') {
        return [
            { href: '/admin/users', label: 'Pengguna', icon: 'users', match: (u) => u.startsWith('/admin/users') },
            { href: '/admin/posts', label: 'Semua Artikel', icon: 'posts', match: (u) => u.startsWith('/admin/posts') },
            { href: '/articles', label: 'Lihat Situs', icon: 'globe', match: () => false },
            { href: '/profile', label: 'Profil Saya', icon: 'user', match: (u) => u.startsWith('/profile') },
        ];
    }
    return [
        { href: '/dashboard/posts', label: 'Artikel Saya', icon: 'posts', match: (u) => u.startsWith('/dashboard') },
        { href: '/articles', label: 'Lihat Situs', icon: 'globe', match: () => false },
        { href: '/profile', label: 'Profil Saya', icon: 'user', match: (u) => u.startsWith('/profile') },
    ];
}

/** Sidebar Tingkat 2 (member) dan Tingkat 3 (admin). */
export default function Sidebar({ open, onClose }) {
    const { auth } = usePage().props;
    const { url } = usePage();
    const user = auth.user;
    const isAdmin = user.role === 'admin';
    const accent = isAdmin ? 'bg-violet-50 text-violet-800' : 'bg-primary-50 text-primary-700';

    return (
        <>
            <aside
                className={`fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 md:sticky md:top-0 md:h-screen md:translate-x-0 ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="px-5 h-16 flex items-center border-b border-slate-100">
                    <Brand href={isAdmin ? '/admin/users' : '/dashboard/posts'} />
                </div>

                <Link href="/profile" className="mx-4 mt-4 p-3 rounded-xl bg-slate-50 border border-slate-200/70 flex items-center gap-3 hover:border-primary-500 transition">
                    <Avatar name={user.name} url={user.avatar_url} path={user.profile_photo_path} className="w-10 h-10" />
                    <span className="min-w-0">
                        <span className="block text-sm font-semibold text-slate-800 truncate">{user.name}</span>
                        <span className="block text-xs text-slate-500 truncate">@{user.username}</span>
                        <span className={`inline-block mt-1 text-[11px] font-semibold px-2 py-0.5 rounded-full ${isAdmin ? 'bg-violet-100 text-violet-800' : 'bg-slate-200 text-slate-700'}`}>
                            {isAdmin ? 'Admin' : 'Member'}
                        </span>
                    </span>
                </Link>

                <nav className="flex-1 px-4 mt-5 space-y-1 overflow-y-auto" aria-label="Navigasi dashboard">
                    {navFor(user.role).map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            onClick={onClose}
                            className={`flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition ${
                                item.match(url) ? `${accent} font-semibold` : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            }`}
                        >
                            <Icon d={ICONS[item.icon]} />
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <div className="p-4 border-t border-slate-100">
                    <button
                        type="button"
                        onClick={() => router.post('/logout')}
                        className="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-rose-600 hover:bg-rose-50 transition"
                    >
                        <Icon d={ICONS.logout} />
                        Keluar
                    </button>
                </div>
            </aside>

            {open && <div onClick={onClose} className="fixed inset-0 z-30 bg-slate-900/40 md:hidden" />}
        </>
    );
}
