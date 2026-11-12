import { Link, usePage } from '@inertiajs/react';
import Brand from './Brand';
import { CATEGORY_NAMES } from '../../lib/constants';

export default function EditorialFooter() {
    const { auth } = usePage().props;

    return (
        <footer className="mt-24 border-t border-slate-200 bg-white">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 py-14 grid gap-12 md:grid-cols-[1.6fr_1fr_1fr]">
                <div>
                    <Brand />
                    <p className="mt-5 max-w-sm font-serif text-2xl leading-snug text-ink">
                        Tulisan yang layak dibaca sampai kalimat terakhir.
                    </p>
                    <p className="mt-3 max-w-sm text-sm text-slate-500">
                        BlogVerse Pro adalah ruang bagi penulis untuk menyusun, menyunting, dan menerbitkan gagasan dalam Bahasa Indonesia.
                    </p>
                </div>

                <nav aria-label="Rubrik">
                    <h2 className="text-sm font-semibold text-slate-900">Rubrik</h2>
                    <ul className="mt-4 space-y-2.5 text-sm">
                        {CATEGORY_NAMES.map((c) => (
                            <li key={c}>
                                <Link href={`/articles?category=${encodeURIComponent(c)}`} className="text-slate-600 hover:text-primary-700">{c}</Link>
                            </li>
                        ))}
                    </ul>
                </nav>

                <nav aria-label="Komunitas">
                    <h2 className="text-sm font-semibold text-slate-900">Komunitas</h2>
                    <ul className="mt-4 space-y-2.5 text-sm">
                        <li><Link href="/articles" className="text-slate-600 hover:text-primary-700">Semua artikel</Link></li>
                        {auth?.user ? (
                            <li><Link href={auth.user.role === 'admin' ? '/admin/users' : '/dashboard/posts'} className="text-slate-600 hover:text-primary-700">Dashboard</Link></li>
                        ) : (
                            <>
                                <li><Link href="/login" className="text-slate-600 hover:text-primary-700">Masuk</Link></li>
                                <li><Link href="/register" className="text-slate-600 hover:text-primary-700">Daftar sebagai penulis</Link></li>
                            </>
                        )}
                    </ul>
                </nav>
            </div>
            <div className="border-t border-slate-100">
                <p className="mx-auto max-w-6xl px-4 sm:px-6 py-5 text-xs text-slate-500">
                    © {new Date().getFullYear()} BlogVerse Pro. Studi kasus PABWE 2026 dengan Laravel, Inertia.js, dan React.
                </p>
            </div>
        </footer>
    );
}
