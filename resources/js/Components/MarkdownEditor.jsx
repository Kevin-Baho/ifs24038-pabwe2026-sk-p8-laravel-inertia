import { useEffect, useRef } from 'react';
import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';

/** TOAST UI Editor (mode Markdown). `value` hanya dipakai sebagai nilai awal dan sinkronisasi eksternal. */
export default function MarkdownEditor({ value = '', onChange, height = '60vh', placeholder = 'Tulis artikel Anda dalam Markdown...' }) {
    const elRef = useRef(null);
    const editorRef = useRef(null);
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    useEffect(() => {
        if (!elRef.current) return;

        editorRef.current = new Editor({
            el: elRef.current,
            height,
            initialEditType: 'markdown',
            previewStyle: window.innerWidth >= 1024 ? 'vertical' : 'tab',
            initialValue: value || '',
            placeholder,
            usageStatistics: false,
            events: {
                change: () => onChangeRef.current?.(editorRef.current.getMarkdown()),
            },
        });

        return () => {
            editorRef.current?.destroy();
            editorRef.current = null;
        };
    }, []);

    useEffect(() => {
        const editor = editorRef.current;
        if (editor && value !== editor.getMarkdown()) {
            editor.setMarkdown(value || '', false);
        }
    }, [value]);

    return (
        <div className="rounded-xl overflow-hidden border border-slate-300 bg-white">
            <div ref={elRef} />
        </div>
    );
}
