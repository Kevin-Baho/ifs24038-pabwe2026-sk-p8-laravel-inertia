import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import MarkdownViewer from '../../Components/MarkdownViewer';
import CategoryBadge from '../../Components/UI/CategoryBadge';
import StatusBadge from '../../Components/UI/StatusBadge';
import Button from '../../Components/UI/Button';
import ConfirmDialog from '../../Components/UI/ConfirmDialog';
import { ArrowLeftIcon, ExternalIcon, PencilIcon, ToggleIcon, TrashIcon } from '../../Components/UI/Icons';

/** Detail artikel: dipakai member (pemilik) dan admin (readOnly). */
export default function PostShow({ post, publicUrl, readOnly = false, author = null }) {
    const [confirming, setConfirming] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const backHref = readOnly ? '/admin/posts' : '/dashboard/posts';

    const remove = () => {
        setDeleting(true);
        router.delete(`/dashboard/posts/${post.id}`, { onFinish: () => setDeleting(false) });
    };

    return (
        <AppLayout>
            <Head title={post.title} />

            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Link href={backHref} className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-primary-700">
                        <ArrowLeftIcon className="w-4 h-4" /> Kembali ke daftar
                    </Link>

                    <div className="flex flex-wrap gap-2">
                        {publicUrl && (
                            <a href={publicUrl} target="_blank" rel="noopener" className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-slate-700 border border-slate-300 hover:bg-slate-50">
                                <ExternalIcon className="w-4 h-4" /> Lihat di situs
                            </a>
                        )}
                        {!readOnly && (
                            <>
                                <Link href={`/dashboard/posts?edit=${post.id}`} className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700">
                                    <PencilIcon className="w-4 h-4" /> Ubah
                                </Link>
                                <Button variant="secondary" onClick={() => router.patch(`/dashboard/posts/${post.id}/toggle-publish`, {}, { preserveScroll: true })}>
                                    <ToggleIcon className="w-4 h-4" /> {post.is_published ? 'Jadikan draf' : 'Publikasikan'}
                                </Button>
                                <Button variant="ghost" onClick={() => setConfirming(true)} className="text-rose-600 hover:bg-rose-50">
                                    <TrashIcon className="w-4 h-4" /> Hapus
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {readOnly && (
                    <p className="rounded-xl bg-violet-50 border border-violet-200 px-4 py-3 text-sm text-violet-900">
                        Mode pantau (read-only){author ? `: artikel milik @${author.username}.` : '.'} Admin tidak dapat mengubah atau menghapus artikel pengguna.
                    </p>
                )}

                <article className="rounded-2xl bg-white border border-slate-200 overflow-hidden">
                    {post.cover_url && <img src={post.cover_url} alt="" className="w-full max-h-96 object-cover" />}
                    <div className="p-6 sm:p-10">
                        <div className="flex flex-wrap items-center gap-2">
                            <CategoryBadge category={post.category} badge={post.category_badge} />
                            <StatusBadge published={post.is_published} />
                        </div>
                        <h1 className="mt-4 font-serif text-3xl sm:text-4xl font-semibold tracking-tight text-ink">{post.title}</h1>
                        {post.excerpt && <p className="mt-3 text-lg text-slate-600">{post.excerpt}</p>}
                        <p className="mt-4 text-sm text-slate-500">
                            Dibuat {post.created_at}. Diperbarui {post.updated_at}. Estimasi baca {post.reading_time} menit.
                        </p>
                        <hr className="my-8 border-slate-200" />
                        <MarkdownViewer content={post.content} />
                    </div>
                </article>
            </div>

            <ConfirmDialog
                open={confirming}
                title="Hapus artikel ini?"
                message={`"${post.title}" akan dihapus permanen.`}
                loading={deleting}
                onConfirm={remove}
                onCancel={() => setConfirming(false)}
            />
        </AppLayout>
    );
}
