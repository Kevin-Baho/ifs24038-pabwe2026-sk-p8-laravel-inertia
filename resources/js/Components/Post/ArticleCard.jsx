import { Link } from '@inertiajs/react';
import CategoryBadge from '../UI/CategoryBadge';
import Avatar from '../Layout/Avatar';

export default function ArticleCard({ post, featured = false }) {
    return (
        <article className="group flex flex-col">
            <Link href={`/articles/${post.slug}`} className="block overflow-hidden rounded-xl bg-slate-100">
                {post.cover_url ? (
                    <img
                        src={post.cover_url} alt="" loading="lazy"
                        className={`w-full object-cover transition-transform duration-500 group-hover:scale-[1.03] ${featured ? 'aspect-[16/9]' : 'aspect-[16/10]'}`}
                    />
                ) : (
                    <div className={`w-full grid place-items-center bg-gradient-to-br from-primary-100 to-slate-100 ${featured ? 'aspect-[16/9]' : 'aspect-[16/10]'}`}>
                        <span className="font-serif text-6xl font-bold text-primary-700/30">{post.title.charAt(0).toUpperCase()}</span>
                    </div>
                )}
            </Link>

            <div className="mt-4 flex flex-col gap-2.5 flex-1">
                <div><CategoryBadge category={post.category} badge={post.category_badge} /></div>
                <h3 className={`font-serif font-semibold tracking-tight text-ink group-hover:text-primary-700 transition-colors ${featured ? 'text-3xl sm:text-4xl leading-tight' : 'text-xl leading-snug'}`}>
                    <Link href={`/articles/${post.slug}`}>{post.title}</Link>
                </h3>
                {post.excerpt && <p className={`text-slate-600 ${featured ? 'text-lg line-clamp-3' : 'text-sm line-clamp-3'}`}>{post.excerpt}</p>}

                <div className="mt-auto pt-2 flex items-center gap-3 text-xs text-slate-500">
                    {post.author && <Avatar name={post.author.name} url={post.author.avatar_url} className="w-6 h-6 text-[11px]" />}
                    <span className="font-medium text-slate-700">{post.author?.name}</span>
                    <span>{post.published_at}</span>
                    <span>{post.reading_time} menit baca</span>
                </div>
            </div>
        </article>
    );
}
