const base = { fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round', viewBox: '0 0 24 24' };

export const EyeIcon = (p) => (
    <svg {...base} {...p}><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" /><circle cx="12" cy="12" r="3" /></svg>
);
export const PencilIcon = (p) => (
    <svg {...base} {...p}><path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" /></svg>
);
export const TrashIcon = (p) => (
    <svg {...base} {...p}><path d="M3 6h18" /><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2" /><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" /><path d="M10 11v6M14 11v6" /></svg>
);
export const DotsVerticalIcon = (p) => (
    <svg viewBox="0 0 24 24" fill="currentColor" {...p}><circle cx="12" cy="5" r="1.8" /><circle cx="12" cy="12" r="1.8" /><circle cx="12" cy="19" r="1.8" /></svg>
);
export const ToggleIcon = (p) => (
    <svg {...base} {...p}><rect x="1" y="7" width="22" height="10" rx="5" /><circle cx="16" cy="12" r="2.5" /></svg>
);
export const PlusIcon = (p) => (
    <svg {...base} {...p}><path d="M12 5v14M5 12h14" /></svg>
);
export const CloseIcon = (p) => (
    <svg {...base} {...p}><path d="M18 6L6 18M6 6l12 12" /></svg>
);
export const SparklesIcon = (p) => (
    <svg {...base} {...p}><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z" /><path d="M19 15l.8 2.200L22 18l-2.200.8L19 21l-.8-2.200L16 18l2.200-.8L19 15z" /></svg>
);
export const DownloadIcon = (p) => (
    <svg {...base} {...p}><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" /><path d="M7 10l5 5 5-5" /><path d="M12 15V3" /></svg>
);
export const UploadIcon = (p) => (
    <svg {...base} {...p}><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" /><path d="M17 8l-5-5-5 5" /><path d="M12 3v12" /></svg>
);
export const SearchIcon = (p) => (
    <svg {...base} {...p}><circle cx="11" cy="11" r="8" /><path d="M21 21l-4.350-4.350" /></svg>
);
export const ArrowLeftIcon = (p) => (
    <svg {...base} {...p}><path d="M19 12H5M12 19l-7-7 7-7" /></svg>
);
export const ExternalIcon = (p) => (
    <svg {...base} {...p}><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6" /><path d="M15 3h6v6" /><path d="M10 14L21 3" /></svg>
);
