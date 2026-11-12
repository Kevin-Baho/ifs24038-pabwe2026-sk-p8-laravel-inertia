import { Link } from '@inertiajs/react';

/** Pagination dari paginator Laravel (`links` array). */
export default function Pagination({ links = [], className = '' }) {
    if (!links || links.length <= 3) return null;

    return (
        <nav className={`flex flex-wrap items-center gap-1 ${className}`} aria-label="Pagination">
            {links.map((link, i) => {
                const label = link.label.replace('&laquo; Previous', '‹').replace('Next &raquo;', '›');
                const cls = `min-w-9 h-9 px-3 inline-flex items-center justify-center rounded-lg text-sm font-medium transition ${
                    link.active ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100'
                } ${!link.url ? 'opacity-40 pointer-events-none' : ''}`;

                return link.url ? (
                    <Link key={i} href={link.url} preserveScroll className={cls} aria-current={link.active ? 'page' : undefined}>
                        {label}
                    </Link>
                ) : (
                    <span key={i} className={cls}>{label}</span>
                );
            })}
        </nav>
    );
}
