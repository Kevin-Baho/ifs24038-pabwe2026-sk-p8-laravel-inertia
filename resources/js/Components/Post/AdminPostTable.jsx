import { Link } from '@inertiajs/react';
import CategoryBadge from '../UI/CategoryBadge';
import StatusBadge from '../UI/StatusBadge';
import { EyeIcon } from '../UI/Icons';

/** Tabel read-only untuk monitoring admin (hanya tombol Detail). */
export default function AdminPostTable({ posts, showAuthor = true }) {
    if (!posts.length) {
        return <p className="px-6 py-14 text-center text-slate-500">Tidak ada artikel yang cocok dengan filter.</p>;
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th className="px-5 py-3 font-semibold">Artikel</th>
                        {showAuthor && <th className="px-5 py-3 font-semibold">Penulis</th>}
                        <th className="px-5 py-3 font-semibold">Kategori</th>
                        <th className="px-5 py-3 font-semibold">Status</th>
                        <th className="px-5 py-3 font-semibold whitespace-nowrap">Dibuat</th>
                        <th className="px-5 py-3 font-semibold text-right">Detail</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {posts.map((p) => (
                        <tr key={p.id} className="hover:bg-slate-50/60">
                            <td className="px-5 py-3.5 max-w-sm">
                                <p className="font-semibold text-slate-900 truncate">{p.title}</p>
                                <p className="text-xs text-slate-500 truncate">/{p.slug}</p>
                            </td>
                            {showAuthor && (
                                <td className="px-5 py-3.5 whitespace-nowrap">
                                    {p.author ? (
                                        <Link href={`/admin/users/${p.author.id}/posts`} className="text-primary-700 hover:underline">@{p.author.username}</Link>
                                    ) : '-'}
                                </td>
                            )}
                            <td className="px-5 py-3.5"><CategoryBadge category={p.category} badge={p.category_badge} /></td>
                            <td className="px-5 py-3.5"><StatusBadge published={p.is_published} /></td>
                            <td className="px-5 py-3.5 whitespace-nowrap text-slate-600">{p.created_at}</td>
                            <td className="px-5 py-3.5 text-right">
                                <Link href={`/admin/posts/${p.id}`} aria-label={`Detail ${p.title}`} className="inline-flex p-2 rounded-lg text-primary-700 hover:bg-primary-50">
                                    <EyeIcon className="w-5 h-5" />
                                </Link>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
