import { useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import Button from '../UI/Button';
import CategoryBadge from '../UI/CategoryBadge';
import { CloseIcon, SparklesIcon } from '../UI/Icons';

const field = 'block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30';

/** Generator draf artikel dengan AI: topik + jumlah draf (input angka) -> pratinjau -> simpan sebagai draf. */
export default function AiGeneratorModal({ open, categories, maxCount = 5, onClose }) {
    const form = useForm({ topic: '', count: 1, category: '' });
    const [items, setItems] = useState([]);
    const [selected, setSelected] = useState({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) return;
        form.reset();
        form.clearErrors();
        setItems([]);
        setSelected({});
    }, [open]);

    useEffect(() => {
        if (!open) return;
        const onKey = (e) => e.key === 'Escape' && !form.processing && !saving && onClose();
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [open, form.processing, saving]);

    if (!open) return null;

    const generate = (e) => {
        e.preventDefault();
        form.transform((d) => ({ ...d, count: Number(d.count) || 1, category: d.category || null }));
        form.post('/dashboard/posts/ai-generate', {
            preserveScroll: true,
            onSuccess: (page) => {
                const result = page.props.flash?.aiGeneratedItems ?? [];
                setItems(result);
                setSelected(Object.fromEntries(result.map((_, i) => [i, true])));
            },
        });
    };

    const chosen = items.filter((_, i) => selected[i]);

    const save = () => {
        setSaving(true);
        router.post('/dashboard/posts/ai-save', { items: chosen }, {
            preserveScroll: true,
            onSuccess: onClose,
            onFinish: () => setSaving(false),
        });
    };

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="ai-title">
            <div className="absolute inset-0 bg-slate-900/50" onClick={() => !form.processing && !saving && onClose()} />
            <div className="relative w-full max-w-3xl max-h-[92vh] flex flex-col rounded-2xl bg-white shadow-2xl overflow-hidden">
                <header className="shrink-0 px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <SparklesIcon className="w-5 h-5 text-primary-100" />
                        <h2 id="ai-title" className="text-lg font-semibold">AI Generator Draf Artikel</h2>
                    </div>
                    <button type="button" aria-label="Tutup" onClick={onClose} className="p-1.5 rounded-lg hover:bg-white/10"><CloseIcon className="w-5 h-5" /></button>
                </header>

                <div className="flex-1 overflow-y-auto p-6 space-y-6">
                    <form id="ai-form" onSubmit={generate} className="grid gap-4 sm:grid-cols-[1fr_9rem]">
                        <div className="sm:col-span-2">
                            <label htmlFor="ai-topic" className="block text-sm font-semibold text-slate-700 mb-1.5">Topik atau arahan</label>
                            <textarea
                                id="ai-topic" rows={3} value={form.data.topic}
                                onChange={(e) => form.setData('topic', e.target.value)}
                                placeholder="Contoh: manfaat membaca buku fisik bagi mahasiswa"
                                className={field}
                            />
                            {form.errors.topic && <p className="mt-1.5 text-sm text-rose-600">{form.errors.topic}</p>}
                        </div>
                        <div>
                            <label htmlFor="ai-category" className="block text-sm font-semibold text-slate-700 mb-1.5">Kategori</label>
                            <select id="ai-category" value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} className={field}>
                                <option value="">Pilih otomatis</option>
                                {categories.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="ai-count" className="block text-sm font-semibold text-slate-700 mb-1.5">Jumlah draf</label>
                            <input
                                id="ai-count" type="number" min={1} max={maxCount} step={1}
                                value={form.data.count}
                                onChange={(e) => form.setData('count', e.target.value)}
                                className={field}
                            />
                            {form.errors.count && <p className="mt-1.5 text-sm text-rose-600">{form.errors.count}</p>}
                        </div>
                    </form>

                    {items.length > 0 && (
                        <section aria-label="Hasil draf">
                            <h3 className="text-sm font-semibold text-slate-700 mb-3">Pilih draf yang akan disimpan</h3>
                            <ul className="space-y-3">
                                {items.map((item, i) => (
                                    <li key={i} className="rounded-xl border border-slate-200 p-4">
                                        <label className="flex items-start gap-3 cursor-pointer">
                                            <input
                                                type="checkbox" checked={!!selected[i]}
                                                onChange={(e) => setSelected((s) => ({ ...s, [i]: e.target.checked }))}
                                                className="mt-1 w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                                            />
                                            <span className="min-w-0">
                                                <span className="flex flex-wrap items-center gap-2">
                                                    <span className="font-semibold text-slate-900">{item.title}</span>
                                                    <CategoryBadge category={item.category} />
                                                </span>
                                                {item.excerpt && <span className="block mt-1 text-sm text-slate-600">{item.excerpt}</span>}
                                            </span>
                                        </label>
                                        <details className="mt-2 ml-7">
                                            <summary className="text-sm font-medium text-primary-700">Lihat isi</summary>
                                            <pre className="mt-2 max-h-64 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-3 text-xs text-slate-700 font-sans">{item.content}</pre>
                                        </details>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                </div>

                <footer className="shrink-0 px-6 py-4 bg-primary-50 border-t border-primary-100 flex items-center justify-end gap-3">
                    <Button variant="secondary" onClick={onClose} disabled={form.processing || saving}>Tutup</Button>
                    <Button type="submit" form="ai-form" variant={items.length ? 'secondary' : 'primary'} loading={form.processing}>
                        {form.processing ? 'Menghasilkan...' : items.length ? 'Hasilkan ulang' : 'Hasilkan draf'}
                    </Button>
                    {items.length > 0 && (
                        <Button onClick={save} loading={saving} disabled={chosen.length === 0}>
                            Simpan {chosen.length} sebagai draf
                        </Button>
                    )}
                </footer>
            </div>
        </div>
    );
}
