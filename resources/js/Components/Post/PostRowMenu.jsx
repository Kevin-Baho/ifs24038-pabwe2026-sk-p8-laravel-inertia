import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { DotsVerticalIcon, EyeIcon, PencilIcon, ToggleIcon, TrashIcon } from '../UI/Icons';

/**
 * Menu tindakan kebab (⋮) per baris.
 * Dirender lewat portal + posisi fixed agar tidak terpotong oleh overflow tabel.
 */
export default function PostRowMenu({ post, onDetail, onEdit, onToggle, onDelete }) {
    const [open, setOpen] = useState(false);
    const [pos, setPos] = useState({ top: 0, left: 0 });
    const btnRef = useRef(null);
    const menuRef = useRef(null);

    const place = () => {
        const rect = btnRef.current?.getBoundingClientRect();
        if (!rect) return;
        const menuH = menuRef.current?.offsetHeight ?? 200;
        const menuW = 224;
        const top = rect.bottom + menuH + 8 > window.innerHeight ? rect.top - menuH - 4 : rect.bottom + 4;
        setPos({ top: Math.max(8, top), left: Math.max(8, rect.right - menuW) });
    };

    useLayoutEffect(() => {
        if (open) place();
    }, [open]);

    useEffect(() => {
        if (!open) return;
        const close = () => setOpen(false);
        const onDown = (e) => {
            if (!menuRef.current?.contains(e.target) && !btnRef.current?.contains(e.target)) close();
        };
        const onKey = (e) => e.key === 'Escape' && close();
        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);
        window.addEventListener('scroll', close, true);
        window.addEventListener('resize', close);
        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
            window.removeEventListener('scroll', close, true);
            window.removeEventListener('resize', close);
        };
    }, [open]);

    const run = (fn) => () => {
        setOpen(false);
        fn();
    };

    const item = 'w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-left transition';

    return (
        <>
            <button
                ref={btnRef}
                type="button"
                aria-label={`Tindakan untuk ${post.title}`}
                aria-haspopup="menu"
                aria-expanded={open}
                onClick={() => setOpen((v) => !v)}
                className="p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800"
            >
                <DotsVerticalIcon className="w-5 h-5" />
            </button>

            {open &&
                createPortal(
                    <div
                        ref={menuRef}
                        role="menu"
                        style={{ top: pos.top, left: pos.left }}
                        className="fixed z-[65] w-56 rounded-xl bg-white py-1.5 shadow-xl ring-1 ring-slate-200"
                    >
                        <button type="button" role="menuitem" onClick={run(onDetail)} className={`${item} text-primary-700 hover:bg-primary-50`}>
                            <EyeIcon className="w-4 h-4" /> Detail
                        </button>
                        <button type="button" role="menuitem" onClick={run(onEdit)} className={`${item} text-primary-700 hover:bg-primary-50`}>
                            <PencilIcon className="w-4 h-4" /> Ubah
                        </button>
                        <button type="button" role="menuitem" onClick={run(onToggle)} className={`${item} text-slate-700 hover:bg-slate-50`}>
                            <ToggleIcon className="w-4 h-4" /> {post.is_published ? 'Jadikan draf' : 'Publikasikan'}
                        </button>
                        <div className="my-1.5 border-t border-slate-100" />
                        <button type="button" role="menuitem" onClick={run(onDelete)} className={`${item} text-rose-600 hover:bg-rose-50`}>
                            <TrashIcon className="w-4 h-4" /> Hapus
                        </button>
                    </div>,
                    document.body,
                )}
        </>
    );
}
