import { useEffect } from 'react';
import Button from './Button';

export default function ConfirmDialog({ open, title, message, confirmLabel = 'Hapus', loading = false, onConfirm, onCancel }) {
    useEffect(() => {
        if (!open) return;
        const onKey = (e) => e.key === 'Escape' && !loading && onCancel();
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [open, loading, onCancel]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-[70] flex items-center justify-center p-4" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title">
            <div className="absolute inset-0 bg-slate-900/50" onClick={() => !loading && onCancel()} />
            <div className="relative w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden">
                <div className="px-6 pt-6 pb-4">
                    <h3 id="confirm-title" className="text-lg font-bold text-slate-900">{title}</h3>
                    <p className="mt-2 text-sm text-slate-600">{message}</p>
                </div>
                <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                    <Button variant="secondary" onClick={onCancel} disabled={loading}>Batal</Button>
                    <Button variant="danger" onClick={onConfirm} loading={loading}>{confirmLabel}</Button>
                </div>
            </div>
        </div>
    );
}
