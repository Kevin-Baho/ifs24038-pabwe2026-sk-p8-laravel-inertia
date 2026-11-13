import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import ArticleCard from '../../Components/Post/ArticleCard';
import Pagination from '../../Components/UI/Pagination';
import { SearchIcon } from '../../Components/UI/Icons';
import useFilters from '../../lib/useFilters';

export default function PublicIndex({ posts, filters, categories }) {
    const [values, set] = useFilters('/articles', { search: filters.search ?? '', category: filters.category ?? '' });

    const chip = (active) =>
        `px-3.5 py-1.5 rounded-full text-sm font-medium border transition ${
            active ? 'bg-primary-700 border-primary-700 text-white' : 'bg-white border-slate-300 text-slate-700 hover:border-primary-500'
        }`;

    return (
        <AppLayout variant="public">
            <Head title="Artikel">
                <meta name="description" content="Telusuri seluruh koleksi artikel, opini, dan tulisan terkini dari para penulis BlogVerse Pro." />
            </Head>

            <div className="mx-auto max-w-6xl px-4 sm:px-6 pt-10 sm:pt-14">
                <h1 className="font-serif text-4xl sm:text-5xl font-semibold tracking-tight text-ink">Semua artikel</h1>
                <p className="mt-2 text-slate-600">Telusuri tulisan dari para penulis BlogVerse Pro.</p>

                <div className="mt-8 flex flex-col gap-4">
                    <div className="relative max-w-xl">
                        <SearchIcon className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input
                            type="search" value={values.search} onChange={(e) => set('search', e.target.value)}
                            placeholder="Cari judul atau ringkasan..." aria-label="Cari artikel"
                            className="w-full rounded-full border border-slate-300 bg-white pl-11 pr-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30"
                        />
                    </div>
                    <div className="flex flex-wrap gap-2" role="group" aria-label="Filter kategori">
                        <button type="button" onClick={() => set('category', '')} className={chip(values.category === '')}>Semua</button>
                        {categories.map((c) => (
                            <button key={c.value} type="button" onClick={() => set('category', c.value)} className={chip(values.category === c.value)}>
                                {c.label}
                            </button>
                        ))}
                    </div>
                </div>

                {posts.data.length ? (
                    <div className="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                        {posts.data.map((p) => <ArticleCard key={p.id} post={p} />)}
                    </div>
                ) : (
                    <div className="mt-16 text-center py-16 rounded-2xl border border-dashed border-slate-300">
                        <p className="font-serif text-2xl text-slate-800">Tidak ada artikel yang cocok.</p>
                        <p className="mt-1 text-sm text-slate-500">Coba kata kunci lain atau <Link href="/articles" className="text-primary-700 font-semibold">hapus filter</Link>.</p>
                    </div>
                )}

                <Pagination links={posts.links} className="mt-12 justify-center" />
            </div>
        </AppLayout>
    );
}
