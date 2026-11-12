import { useEffect, useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import MarkdownEditor from '../MarkdownEditor';
import Button from '../UI/Button';
import { CloseIcon } from '../UI/Icons';

const field = 'block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30';

/**
 * Modal Full Screen untuk Tambah / Ubah artikel.
 * Header gelap (slate) dan footer terang (indigo) untuk kontras; tombol aksi di kanan footer.
 */
export default function PostFormModal({ open, post, categories, onClose }) {
    const isEdit = Boolean(post);
    const form = useForm({
        title: '',
        category: 'Umum',
        excerpt: '',
        content: '',
        cover: null,
        remove_cover: false,
        is_published: false,
    });
    const [coverPreview, setCoverPreview] = useState(null);

    useEffect(() => {
        if (!open) return;
        form.setData({
            title: post?.title ?? '',
            category: post?.category ?? 'Umum',
            excerpt: post?.excerpt ?? '',
            content: post?.content ?? '',
            cover: null,
            remove_cover: false,
            is_published: post?.is_published ?? false,
        });
        form.clearErrors();
        setCoverPreview(null);
    }, [open, post?.id]);

    useEffect(() => {
        if (!open) return;
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const onKey = (e) => e.key === 'Escape' && !form.processing && onClose();
        window.addEventListener('keydown', onKey);
        return () => {
            document.body.style.overflow = prev;
            window.removeEventListener('keydown', onKey);
        };
    }, [open, form.processing]);

    useEffect(() => () => coverPreview && URL.revokeObjectURL(coverPreview), [coverPreview]);

    const shownCover = useMemo(() => {
        if (coverPreview) return coverPreview;
        if (isEdit && !form.data.remove_cover) return post?.cover_url ?? null;
        return null;
    }, [coverPreview, isEdit, form.data.remove_cover, post?.cover_url]);

    if (!open) return null;

    const pickCover = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        form.setData((d) => ({ ...d, cover: file, remove_cover: false }));
        setCoverPreview(URL.createObjectURL(file));
    };

    const removeCover = () => {
        form.setData((d) => ({ ...d, cover: null, remove_cover: true }));
        setCoverPreview(null);
    };

    const submit = (e) => {
        e.preventDefault();
        const options = { forceFormData: true, preserveScroll: true, onSuccess: onClose };

        // Laravel tidak membaca multipart pada PUT, jadi gunakan method spoofing.
        form.transform((d) => (isEdit ? { ...d, _method: 'put' } : d));
        form.post(isEdit ? `/dashboard/posts/${post.id}` : '/dashboard/posts', options);
    };

    return (
        <div className="fixed inset-0 z-[60] flex flex-col bg-white" role="dialog" aria-modal="true" aria-labelledby="post-modal-title">
            {/* Header */}
            <header className="shrink-0 h-16 px-4 sm:px-6 flex items-center justify-between bg-slate-900 text-white">
                <div>
                    <h2 id="post-modal-title" className="text-lg font-semibold leading-tight">
                        {isEdit ? 'Ubah Artikel' : 'Tambah Artikel'}
                    </h2>
                    <p className="text-xs text-slate-300 hidden sm:block">Konten ditulis dalam Markdown.</p>
                </div>
                <button type="button" aria-label="Tutup" onClick={onClose} disabled={form.processing} className="p-2 rounded-lg hover:bg-white/10 disabled:opacity-50">
                    <CloseIcon className="w-5 h-5" />
                </button>
            </header>

            {/* Body */}
            <div className="flex-1 overflow-y-auto bg-slate-50">
                <form id="post-form" onSubmit={submit} className="mx-auto max-w-5xl px-4 sm:px-6 py-6 space-y-6">
                    <div className="grid gap-5 md:grid-cols-[1fr_16rem]">
                        <div className="space-y-5">
                            <div>
                                <label htmlFor="post-title" className="block text-sm font-semibold text-slate-700 mb-1.5">Judul</label>
                                <input id="post-title" type="text" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} className={field} placeholder="Judul artikel" autoFocus />
                                {form.errors.title && <p className="mt-1.5 text-sm text-rose-600">{form.errors.title}</p>}
                            </div>

                            <div>
                                <label htmlFor="post-excerpt" className="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Ringkasan <span className="font-normal text-slate-500">(opsional, otomatis dari isi bila kosong)</span>
                                </label>
                                <textarea id="post-excerpt" rows={3} value={form.data.excerpt} onChange={(e) => form.setData('excerpt', e.target.value)} className={field} />
                                {form.errors.excerpt && <p className="mt-1.5 text-sm text-rose-600">{form.errors.excerpt}</p>}
                            </div>
                        </div>

                        <div className="space-y-5">
                            <div>
                                <label htmlFor="post-category" className="block text-sm font-semibold text-slate-700 mb-1.5">Kategori</label>
                                <select id="post-category" value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} className={field}>
                                    {categories.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                </select>
                                {form.errors.category && <p className="mt-1.5 text-sm text-rose-600">{form.errors.category}</p>}
                            </div>

                            <label className="flex items-center gap-3 rounded-xl border border-slate-300 bg-white px-4 py-3 cursor-pointer">
                                <input
                                    id="post-published"
                                    type="checkbox"
                                    checked={form.data.is_published}
                                    onChange={(e) => form.setData('is_published', e.target.checked)}
                                    className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                                />
                                <span className="text-sm">
                                    <span className="block font-semibold text-slate-800">Terbitkan</span>
                                    <span className="block text-xs text-slate-500">Jika mati, artikel tersimpan sebagai draf.</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    {/* Cover */}
                    <div>
                        <span className="block text-sm font-semibold text-slate-700 mb-1.5">Gambar sampul</span>
                        <div className="flex flex-wrap items-center gap-4 rounded-xl border border-dashed border-slate-300 bg-white p-4">
                            {shownCover ? (
                                <img src={shownCover} alt="Pratinjau sampul" className="h-24 w-40 rounded-lg object-cover border border-slate-200" />
                            ) : (
                                <div className="h-24 w-40 rounded-lg bg-slate-100 grid place-items-center text-xs text-slate-500">Belum ada sampul</div>
                            )}
                            <div className="flex flex-wrap gap-2">
                                <label className="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 cursor-pointer">
                                    Pilih gambar
                                    <input type="file" accept="image/png,image/jpeg,image/webp" className="sr-only" onChange={pickCover} />
                                </label>
                                {shownCover && (
                                    <Button variant="ghost" onClick={removeCover} className="text-rose-600 hover:bg-rose-50">Hapus sampul</Button>
                                )}
                            </div>
                            <p className="text-xs text-slate-500 w-full">JPG, PNG, atau WebP. Maksimal 5 MB.</p>
                        </div>
                        {form.errors.cover && <p className="mt-1.5 text-sm text-rose-600">{form.errors.cover}</p>}
                    </div>

                    {/* Editor */}
                    <div>
                        <span className="block text-sm font-semibold text-slate-700 mb-1.5">Konten</span>
                        <MarkdownEditor value={form.data.content} onChange={(md) => form.setData('content', md)} height="55vh" />
                        {form.errors.content && <p className="mt-1.5 text-sm text-rose-600">{form.errors.content}</p>}
                    </div>
                </form>
            </div>

            {/* Footer: tombol aksi di kanan */}
            <footer className="shrink-0 px-4 sm:px-6 py-4 bg-primary-50 border-t border-primary-100 flex items-center justify-end gap-3">
                <Button variant="secondary" onClick={onClose} disabled={form.processing}>Batal</Button>
                <Button type="submit" form="post-form" loading={form.processing}>
                    {form.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Simpan Artikel'}
                </Button>
            </footer>
        </div>
    );
}
