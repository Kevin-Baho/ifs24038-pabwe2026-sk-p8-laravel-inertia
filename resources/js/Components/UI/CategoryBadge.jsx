export default function CategoryBadge({ category, badge, className = '' }) {
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset ${badge || 'bg-slate-100 text-slate-700 ring-slate-200'} ${className}`}>
            {category}
        </span>
    );
}
