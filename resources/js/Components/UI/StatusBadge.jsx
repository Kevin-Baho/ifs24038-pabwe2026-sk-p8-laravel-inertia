export default function StatusBadge({ published }) {
    return published ? (
        <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200">
            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />Terbit
        </span>
    ) : (
        <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-900 ring-1 ring-inset ring-amber-200">
            <span className="w-1.5 h-1.5 rounded-full bg-amber-500" />Draf
        </span>
    );
}
