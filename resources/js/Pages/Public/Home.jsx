import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import ArticleCard from '../../Components/Post/ArticleCard';
import CommunityCta from '../../Components/Post/CommunityCta';

export default function Home({ latest, categories }) {
    const [lead, ...rest] = latest;

    return (
        <AppLayout variant="public">
            <Head title="Beranda">
                <meta name="description" content="BlogVerse Pro - Platform publikasi artikel dan jurnalisme independen dengan kurasi konten berkualitas." />
            </Head>

            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <h1 className="sr-only">BlogVerse Pro - Beranda Utama</h1>

                {/* Berita utama + indeks rubrik */}
                <section className="pt-10 sm:pt-14 grid gap-10 lg:grid-cols-[1fr_17rem]">
                    <div>
                        {lead ? (
                            <ArticleCard post={lead} featured />
                        ) : (
                            <div className="rounded-2xl bg-white border border-slate-200 p-10">
                                <h2 className="font-serif text-4xl font-semibold tracking-tight text-ink">Halaman pertama masih kosong.</h2>
                                <p className="mt-3 max-w-lg text-slate-600">Belum ada artikel yang terbit. Jadilah penulis pertama di BlogVerse Pro.</p>
                                <Link href="/register" className="mt-6 inline-flex px-5 py-3 rounded-xl bg-primary-600 text-white font-semibold hover:bg-primary-700">Daftar sebagai penulis</Link>
                            </div>
                        )}
                    </div>

                    <aside aria-labelledby="rubrik-title" className="lg:border-l lg:border-slate-200 lg:pl-8">
                        <h2 id="rubrik-title" className="font-serif text-2xl font-semibold text-ink">Rubrik</h2>
                        <ul className="mt-4 divide-y divide-slate-200 border-y border-slate-200">
                            {categories.map((c) => (
                                <li key={c.value}>
                                    <Link href={`/articles?category=${encodeURIComponent(c.value)}`} className="flex items-baseline justify-between py-3 text-slate-700 hover:text-primary-700">
                                        <span className="font-medium">{c.label}</span>
                                        <span className="text-sm tabular-nums text-slate-500">{c.count} artikel</span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </aside>
                </section>

                {/* Terbaru */}
                {rest.length > 0 && (
                    <section className="mt-16" aria-labelledby="latest-title">
                        <div className="flex items-end justify-between border-b border-slate-200 pb-3">
                            <h2 id="latest-title" className="font-serif text-3xl font-semibold text-ink">Tulisan terbaru</h2>
                            <Link href="/articles" className="text-sm font-semibold text-primary-700 hover:underline">Lihat semua artikel</Link>
                        </div>
                        <div className="mt-8 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                            {rest.map((p) => <ArticleCard key={p.id} post={p} />)}
                        </div>
                    </section>
                )}

                <div className="mt-20"><CommunityCta /></div>
            </div>
        </AppLayout>
    );
}
