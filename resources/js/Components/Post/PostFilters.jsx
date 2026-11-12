import useFilters from '../../lib/useFilters';
import { SearchIcon } from '../UI/Icons';

const field = 'rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30';

/** Pencarian + filter status + filter kategori (disinkronkan ke query string). */
export default function PostFilters({ url, filters, categories }) {
    const [values, set] = useFilters(url, {
        search: filters.search ?? '',
        status: filters.status ?? '',
        category: filters.category ?? '',
    });

    return (
        <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
                <SearchIcon className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input
                    type="search" value={values.search} onChange={(e) => set('search', e.target.value)}
                    placeholder="Cari judul atau ringkasan..." aria-label="Cari artikel"
                    className={`${field} w-full pl-10`}
                />
            </div>
            <select value={values.status} onChange={(e) => set('status', e.target.value)} aria-label="Filter status" className={field}>
                <option value="">Semua status</option>
                <option value="published">Terbit</option>
                <option value="draft">Draf</option>
            </select>
            <select value={values.category} onChange={(e) => set('category', e.target.value)} aria-label="Filter kategori" className={field}>
                <option value="">Semua kategori</option>
                {categories.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
            </select>
        </div>
    );
}
