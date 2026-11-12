import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import MarkdownViewer from '../../Components/MarkdownViewer';
import ArticleCard from '../../Components/Post/ArticleCard';
import CommunityCta from '../../Components/Post/CommunityCta';
import CategoryBadge from '../../Components/UI/CategoryBadge';
import Avatar from '../../Components/Layout/Avatar';

/** Halaman baca: kolom sempit, tipografi serif, Markdown dirender TOAST UI Viewer. */
export default function PublicShow({ post, related }) {
    return (
        <AppLayout variant="public">
            <Head title={post.title}>
                {post.excerpt && <meta name="description" content={post.excerpt} />}
            </Head>

            <article className="mx-auto max-w-3xl px-4 sm:px-6 pt-10 sm:pt-14">
                <Link href={`/articles?category=${encodeURIComponent(post.category)}`} className="inline-block">
                    <CategoryBadge category={post.category} badge={post.category_badge} />
                </Link>

                <h1 className="mt-5 font-serif text-4xl sm:text-5xl font-semibold tracking-tight leading-[1.1] text-ink">{post.title}</h1>
                {post.excerpt && <p className="mt-5 font-serif text-xl text-slate-600 leading-relaxed">{post.excerpt}</p>}

                <div className="mt-7 flex items-center gap-3 text-sm text-slate-500">
                    {post.author && <Avatar name={post.author.name} url={post.author.avatar_url} className="w-10 h-10" />}
                    <div>
                        <p className="font-semibold text-slate-800">{post.author?.name}</p>
                        <p>{post.published_at}, {post.reading_time} menit baca</p>
                    </div>
                </div>

                {post.cover_url && <img src={post.cover_url} alt="" className="mt-10 w-full rounded-2xl object-cover max-h-[28rem]" />}

                <div className="mt-10">
                    <MarkdownViewer content={post.content} />
                </div>
            </article>

            <div className="mx-auto max-w-5xl px-4 sm:px-6 mt-20"><CommunityCta /></div>

            {related.length > 0 && (
                <section className="mx-auto max-w-6xl px-4 sm:px-6 mt-20" aria-labelledby="related-title">
                    <h2 id="related-title" className="font-serif text-2xl font-semibold text-ink border-b border-slate-200 pb-3">Bacaan lainnya</h2>
                    <div className="mt-8 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                        {related.map((p) => <ArticleCard key={p.id} post={p} />)}
                    </div>
                </section>
            )}
        </AppLayout>
    );
}
