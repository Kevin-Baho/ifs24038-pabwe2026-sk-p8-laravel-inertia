import { useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import PostFormModal from '../../Components/Post/PostFormModal';
import AiGeneratorModal from '../../Components/Post/AiGeneratorModal';
import PostRowMenu from '../../Components/Post/PostRowMenu';
import PostFilters from '../../Components/Post/PostFilters';
import CategoryBadge from '../../Components/UI/CategoryBadge';
import StatusBadge from '../../Components/UI/StatusBadge';
import StatCards from '../../Components/UI/StatCards';
import ConfirmDialog from '../../Components/UI/ConfirmDialog';
import Pagination from '../../Components/UI/Pagination';
import Button from '../../Components/UI/Button';
import { DownloadIcon, PlusIcon, SparklesIcon, UploadIcon } from '../../Components/UI/Icons';

export default function PostIndex({ posts, filters, stats, categories, aiMaxCount, editing }) {
    const [formOpen, setFormOpen] = useState(Boolean(editing));
    const [editTarget, setEditTarget] = useState(editing ?? null);
    const [aiOpen, setAiOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [importing, setImporting] = useState(false);
    const fileRef = useRef(null);

    const openCreate = () => { setEditTarget(null); setFormOpen(true); };
    const openEdit = (post) => { setEditTarget(post); setFormOpen(true); };

    const confirmDelete = () => {
        setDeleting(true);
        router.delete(`/dashboard/posts/${deleteTarget.id}`, {
            preserveScroll: true,
            onFinish: () => { setDeleting(false); setDeleteTarget(null); },
        });
    };

    const onImport = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setImporting(true);
        router.post('/dashboard/posts/import', { importFile: file }, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => { setImporting(false); if (fileRef.current) fileRef.current.value = ''; },
        });
    };

    return (
        <AppLayout>
            <Head title="Artikel Saya" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Artikel Saya</h1>
                        <p className="text-sm text-slate-500">Tulis, sunting, dan terbitkan artikel Anda.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <a href="/dashboard/posts/export" className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 transition">
                            <DownloadIcon className="w-4 h-4" /> Ekspor Excel
                        </a>
                        <Button variant="secondary" onClick={() => fileRef.current?.click()} loading={importing}>
                            {!importing && <UploadIcon className="w-4 h-4" />} Impor Excel
                        </Button>
                        <input ref={fileRef} type="file" accept=".xlsx,.xls,.csv" className="sr-only" onChange={onImport} aria-label="Berkas Excel untuk diimpor" />
                        <Button variant="secondary" onClick={() => setAiOpen(true)} className="text-primary-700 border-primary-100 bg-primary-50 hover:bg-primary-100">
                            <SparklesIcon className="w-4 h-4" /> AI Generator
                        </Button>
                        <Button onClick={openCreate}><PlusIcon className="w-4 h-4" /> Tambah Artikel</Button>
                    </div>
                </div>

                <StatCards
                    items={[
                        { label: 'Total Artikel', value: stats.total, accent: 'bg-primary-600' },
                        { label: 'Total Selesai (Terbit)', value: stats.published, accent: 'bg-emerald-500' },
                        { label: 'Belum Selesai (Draf)', value: stats.draft, accent: 'bg-amber-500' },
                    ]}
                />

                <section className="rounded-2xl bg-white border border-slate-200">
                    <div className="p-4 border-b border-slate-100">
                        <PostFilters url="/dashboard/posts" filters={filters} categories={categories} />
                    </div>

                    {posts.data.length === 0 ? (
                        <div className="px-6 py-16 text-center">
                            <p className="font-serif text-2xl text-slate-800">Belum ada artikel di sini.</p>
                            <p className="mt-1 text-sm text-slate-500">Mulai dengan menulis sendiri atau minta AI menyiapkan draf awal.</p>
                            <Button onClick={openCreate} className="mt-5"><PlusIcon className="w-4 h-4" /> Tulis artikel pertama</Button>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-slate-600">
                                    <tr>
                                        <th className="px-5 py-3 font-semibold">Artikel</th>
                                        <th className="px-5 py-3 font-semibold">Kategori</th>
                                        <th className="px-5 py-3 font-semibold">Status</th>
                                        <th className="px-5 py-3 font-semibold whitespace-nowrap">Diperbarui</th>
                                        <th className="px-3 py-3 w-14"><span className="sr-only">Tindakan</span></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {posts.data.map((post) => (
                                        <tr key={post.id} className="hover:bg-slate-50/60">
                                            <td className="px-5 py-3.5 max-w-md">
                                                <div className="flex items-center gap-3">
                                                    {post.cover_url ? (
                                                        <img src={post.cover_url} alt="" className="w-12 h-12 rounded-lg object-cover border border-slate-200 shrink-0" />
                                                    ) : (
                                                        <span className="w-12 h-12 rounded-lg bg-primary-50 text-primary-700 font-serif font-bold text-lg grid place-items-center shrink-0">
                                                            {post.title.charAt(0).toUpperCase()}
                                                        </span>
                                                    )}
                                                    <div className="min-w-0">
                                                        <button type="button" onClick={() => router.visit(`/dashboard/posts/${post.id}`)} className="block max-w-full truncate text-left font-semibold text-slate-900 hover:text-primary-700">
                                                            {post.title}
                                                        </button>
                                                        <p className="text-xs text-slate-500 truncate">/articles/{post.slug}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3.5"><CategoryBadge category={post.category} badge={post.category_badge} /></td>
                                            <td className="px-5 py-3.5"><StatusBadge published={post.is_published} /></td>
                                            <td className="px-5 py-3.5 whitespace-nowrap text-slate-600">{post.updated_at}</td>
                                            <td className="px-3 py-3.5 text-right">
                                                <PostRowMenu
                                                    post={post}
                                                    onDetail={() => router.visit(`/dashboard/posts/${post.id}`)}
                                                    onEdit={() => openEdit(post)}
                                                    onToggle={() => router.patch(`/dashboard/posts/${post.id}/toggle-publish`, {}, { preserveScroll: true })}
                                                    onDelete={() => setDeleteTarget(post)}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <div className="px-5 py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
                        <span>{posts.total ? `Menampilkan ${posts.from}-${posts.to} dari ${posts.total} artikel` : ''}</span>
                        <Pagination links={posts.links} />
                    </div>
                </section>
            </div>

            <PostFormModal open={formOpen} post={editTarget} categories={categories} onClose={() => setFormOpen(false)} />
            <AiGeneratorModal open={aiOpen} categories={categories} maxCount={aiMaxCount} onClose={() => setAiOpen(false)} />
            <ConfirmDialog
                open={Boolean(deleteTarget)}
                title="Hapus artikel ini?"
                message={deleteTarget ? `"${deleteTarget.title}" akan dihapus permanen beserta gambar sampulnya.` : ''}
                loading={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
