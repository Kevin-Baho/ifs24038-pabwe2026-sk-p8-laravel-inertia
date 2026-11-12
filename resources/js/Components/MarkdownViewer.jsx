import { useEffect, useRef } from 'react';
import Viewer from '@toast-ui/editor/dist/toastui-editor-viewer';
import '@toast-ui/editor/dist/toastui-editor-viewer.css';

/** TOAST UI Viewer: render Markdown (read-only). */
export default function MarkdownViewer({ content = '' }) {
    const elRef = useRef(null);
    const viewerRef = useRef(null);

    useEffect(() => {
        if (!elRef.current) return;
        viewerRef.current = new Viewer({ el: elRef.current, initialValue: content || '', usageStatistics: false });
        return () => {
            viewerRef.current?.destroy();
            viewerRef.current = null;
        };
    }, []);

    useEffect(() => {
        viewerRef.current?.setMarkdown(content || '');
    }, [content]);

    return (
        <div className="article-body">
            <div ref={elRef} />
        </div>
    );
}
